// Deploy as a web app executing as your account, with access set to Anyone.
// Requests still require the HMAC secret stored in Script Properties.
// Script property: IMS_REPORT_SECRET (same value as private upload.json).
const REPORT_FOLDERS = {
  inbound: '1pgofU7y8uC4Gnj1qFM4_KbhGPqbMg01b',
  outbound: '1jAefS1MeW2ccNmipPjg23wulSzmFcOww',
  barcode_history: '1bYtJCXUGEkWcIlrdh2O-DTNIGSK9kTGe'
};
const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

function doPost(e) {
  const json = value => ContentService.createTextOutput(JSON.stringify(value))
    .setMimeType(ContentService.MimeType.JSON);
  const lock = LockService.getScriptLock();
  try {
    const request = JSON.parse(e.postData.contents);
    const secret = PropertiesService.getScriptProperties().getProperty('IMS_REPORT_SECRET');
    if (!secret || typeof request.payload !== 'string') throw new Error('Unauthorized');
    const expected = Utilities.computeHmacSha256Signature(request.payload, secret, Utilities.Charset.UTF_8)
      .map(b => ((b + 256) % 256).toString(16).padStart(2, '0')).join('');
    if (request.signature !== expected) throw new Error('Unauthorized');

    const report = JSON.parse(request.payload);
    // Accept the prior PHP uploader's .csv request name during VPS rollout.
    // The Drive artifact is always a formatted .xlsx workbook.
    const nameMatch = /^(inbound|outbound|barcode_history)_(\d{4}-\d{2}-\d{2})\.(?:csv|xlsx)$/.exec(report.name || '');
    if (!nameMatch || typeof report.csv !== 'string' || !report.csv.length) {
      throw new Error('Invalid report');
    }
    const kind = nameMatch[1];
    const fileName = kind + '_' + nameMatch[2] + '.xlsx';
    const values = Utilities.parseCsv(report.csv);
    if (!values.length || !values[0].length) throw new Error('Report has no header row');
    const columns = Math.max.apply(null, values.map(row => row.length));
    if (values.length > 1048576 || columns > 16384) throw new Error('Report exceeds Excel worksheet limits');
    const table = values.map(row => {
      const normalized = row.slice();
      while (normalized.length < columns) normalized.push('');
      return normalized;
    });

    lock.waitLock(30000);
    const folder = DriveApp.getFolderById(REPORT_FOLDERS[kind]);
    const matches = folder.getFilesByName(fileName);
    let previousFile = null;
    if (matches.hasNext()) {
      previousFile = matches.next();
      if (matches.hasNext()) throw new Error('Duplicate report filenames need review');
      if (previousFile.getMimeType() !== XLSX_MIME) {
        throw new Error('An existing file has the report filename but is not an Excel workbook');
      }
    }

    const workbook = createWorkbookBlob(kind, fileName, table);
    if (previousFile) previousFile.setTrashed(true);
    let file;
    try {
      file = folder.createFile(workbook);
    } catch (error) {
      if (previousFile) previousFile.setTrashed(false);
      throw error;
    }
    // Echo the request name so both the old CSV-name client and the new XLSX
    // client can verify their upload; Drive always stores fileName above.
    return json({ok: true, id: file.getId(), name: report.name, mimeType: XLSX_MIME, folder: kind});
  } catch (error) {
    console.error(error.message);
    return json({ok: false, error: error.message});
  } finally {
    if (lock.hasLock()) lock.releaseLock();
  }
}

function createWorkbookBlob(kind, fileName, table) {
  const escapeXml = value => String(value == null ? '' : value)
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&apos;');
  const columnName = number => {
    let name = '';
    while (number > 0) {
      const remainder = (number - 1) % 26;
      name = String.fromCharCode(65 + remainder) + name;
      number = Math.floor((number - 1) / 26);
    }
    return name;
  };
  const preferredWidths = {
    Barcode: 21, Description: 38, Supplier: 27, Customer: 30,
    'Supplier Return (RTS) Status (as of report run)': 48,
    'Reason for Supplier Return': 36, 'Supplier Return Reference': 28,
    'Received By': 25, 'Prepared By': 25,
    Warehouse: 24, 'Date Acquired': 22, 'Date Sent': 22,
    'Event Date': 22, 'Activity Type': 42, 'Reason': 40,
    'Result / Status': 36, 'Reference No.': 30,
    'Related Inbound ID': 22, 'Related Outbound Reference': 34,
    'Customer Order Number': 24, 'Customer': 30,
    'Customer Return Fault / Supplier Requested Outcome': 42,
    'Customer Return Fault Type': 26, 'Recorded By': 25
  };
  const reportDate = (fileName.match(/_(\d{4}-\d{2}-\d{2})\.xlsx$/) || [])[1] || '';
  const reportGuides = {
    inbound: {
      title: 'Daily Inbound Report',
      note: 'Each row is an item received on this date. RTS means Return to Supplier; those columns show later supplier-return activity for these barcodes, as of the report run. Customer returns belong in the Outbound report.'
    },
    outbound: {
      title: 'Daily Outbound Report',
      note: 'Each row is an item sent to a customer on this date. Customer Returns are items brought back by customers; these details match the same outbound transaction. Supplier RTS details belong in the Inbound report.'
    },
    barcode_history: {
      title: 'Returns & Supplier RTS — Barcode Activity History',
      note: 'Each row is one event, so a barcode can appear more than once. Customer Returns link to an outbound order; Return to Supplier (RTS) events link to the original inbound record. These are separate processes.'
    }
  };
  const guide = reportGuides[kind] || {title: 'Daily Inventory Report', note: ''};
  const displayTable = [
    [guide.title],
    [`Report date: ${reportDate}`],
    [guide.note],
    ...table
  ];
  const headerRowNumber = 4;
  const lastColumn = columnName(table[0].length);
  const cols = table[0].map((title, index) =>
    `<col min="${index + 1}" max="${index + 1}" width="${preferredWidths[title] || 20}" customWidth="1"/>`
  ).join('');
  const rows = displayTable.map((row, rowIndex) => {
    const cells = row.map((value, columnIndex) => {
      const ref = columnName(columnIndex + 1) + (rowIndex + 1);
      const style = rowIndex === 0 ? 3 : rowIndex === headerRowNumber - 1 ? 1 : rowIndex < headerRowNumber - 1 ? 4 : 2;
      return `<c r="${ref}" s="${style}" t="inlineStr"><is><t xml:space="preserve">${escapeXml(value)}</t></is></c>`;
    }).join('');
    const height = rowIndex === 0 ? 34 : rowIndex === 2 ? 44 : rowIndex === headerRowNumber - 1 ? 38 : 22;
    return `<row r="${rowIndex + 1}" ht="${height}" customHeight="1">${cells}</row>`;
  }).join('');
  const lastCell = lastColumn + displayTable.length;
  const filter = `<autoFilter ref="A${headerRowNumber}:${lastCell}"/>`;
  const mergedGuides = [1, 2, 3].map(row =>
    `<mergeCell ref="A${row}:${lastColumn}${row}"/>`
  ).join('');
  const merges = `<mergeCells count="3">${mergedGuides}</mergeCells>`;

  const parts = [
    ['[Content_Types].xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>`],
    ['_rels/.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`],
    ['xl/workbook.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews><sheets><sheet name="${kind === 'inbound' ? 'Inbound' : kind === 'outbound' ? 'Outbound' : 'Returns &amp; Supplier RTS'}" sheetId="1" r:id="rId1"/></sheets></workbook>`],
    ['xl/_rels/workbook.xml.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`],
    ['xl/styles.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><color rgb="FF202124"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font><font><sz val="10"/><color rgb="FF44546A"/><name val="Calibri"/><family val="2"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8EAED"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor rgb="FF1F4E78"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor rgb="FFD9EAF7"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDADCE0"/></left><right style="thin"><color rgb="FFDADCE0"/></right><top style="thin"><color rgb="FFDADCE0"/></top><bottom style="thin"><color rgb="FFDADCE0"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>`],
    ['xl/worksheets/sheet1.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:${lastCell}"/><sheetViews><sheetView showGridLines="1" tabSelected="1" workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A5" sqref="A5"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="20"/><cols>${cols}</cols><sheetData>${rows}</sheetData>${filter}${merges}<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>`]
  ];
  const blobs = parts.map(([path, content]) => Utilities.newBlob(content, 'application/xml', path));
  const zipped = Utilities.zip(blobs, fileName);
  return Utilities.newBlob(zipped.getBytes(), XLSX_MIME, fileName);
}
