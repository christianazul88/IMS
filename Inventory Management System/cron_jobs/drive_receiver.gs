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
    'RTS Reason': 34, 'Inbounded by': 25, 'Prepared By': 25,
    Warehouse: 24, 'Date Acquired': 22, 'Date Sent': 22,
    'Event Date': 22, 'Event Type': 25, 'Reason': 40,
    'Status / Outcome': 36, 'Event Reference': 38, 'Inbound ID': 16,
    'Outbound No.': 34, 'Order No.': 24, 'Customer': 30,
    'Fault / RTS For': 24, 'Fault Type': 22, 'Recorded By': 25
  };
  const cols = table[0].map((title, index) =>
    `<col min="${index + 1}" max="${index + 1}" width="${preferredWidths[title] || 20}" customWidth="1"/>`
  ).join('');
  const rows = table.map((row, rowIndex) => {
    const cells = row.map((value, columnIndex) => {
      const ref = columnName(columnIndex + 1) + (rowIndex + 1);
      const style = rowIndex === 0 ? 1 : 2;
      return `<c r="${ref}" s="${style}" t="inlineStr"><is><t xml:space="preserve">${escapeXml(value)}</t></is></c>`;
    }).join('');
    return `<row r="${rowIndex + 1}"${rowIndex === 0 ? ' ht="34" customHeight="1"' : ''}>${cells}</row>`;
  }).join('');
  const lastCell = columnName(table[0].length) + table.length;
  const filter = table.length > 1 ? `<autoFilter ref="A1:${lastCell}"/>` : '';

  const parts = [
    ['[Content_Types].xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>`],
    ['_rels/.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`],
    ['xl/workbook.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews><sheets><sheet name="${kind === 'inbound' ? 'Inbound' : kind === 'outbound' ? 'Outbound' : 'Barcode History'}" sheetId="1" r:id="rId1"/></sheets></workbook>`],
    ['xl/_rels/workbook.xml.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`],
    ['xl/styles.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><color rgb="FF202124"/><name val="Calibri"/><family val="2"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8EAED"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDADCE0"/></left><right style="thin"><color rgb="FFDADCE0"/></right><top style="thin"><color rgb="FFDADCE0"/></top><bottom style="thin"><color rgb="FFDADCE0"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>`],
    ['xl/worksheets/sheet1.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:${lastCell}"/><sheetViews><sheetView showGridLines="1" tabSelected="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="20"/><cols>${cols}</cols><sheetData>${rows}</sheetData>${filter}<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>`]
  ];
  const blobs = parts.map(([path, content]) => Utilities.newBlob(content, 'application/xml', path));
  const zipped = Utilities.zip(blobs, fileName);
  return Utilities.newBlob(zipped.getBytes(), XLSX_MIME, fileName);
}
