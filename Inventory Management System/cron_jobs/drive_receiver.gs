// Deploy as a web app executing as your account, with access set to Anyone.
// Requests still require the HMAC secret stored in Script Properties.
// Script property: IMS_REPORT_SECRET (same value as private upload.json).
const REPORT_FOLDER = '1FG7fL_0EqiExd92K4MQKOUioFftiz9Z1';

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
    if (!/^(inbound|outbound)_\d{4}-\d{2}-\d{2}\.csv$/.test(report.name) || typeof report.csv !== 'string') {
      throw new Error('Invalid report');
    }
    lock.waitLock(30000);
    const folder = DriveApp.getFolderById(REPORT_FOLDER);
    const matches = folder.getFilesByName(report.name);
    let file;
    if (matches.hasNext()) {
      file = matches.next();
      if (matches.hasNext()) throw new Error('Duplicate report filenames need review');
      if (file.getMimeType() !== MimeType.CSV) throw new Error('Existing file is not CSV');
      file.setContent(report.csv);
    } else {
      file = folder.createFile(Utilities.newBlob(report.csv, MimeType.CSV, report.name));
    }
    return json({ok: true, id: file.getId(), name: file.getName()});
  } catch (error) {
    console.error(error.message);
    return json({ok: false, error: error.message});
  } finally {
    if (lock.hasLock()) lock.releaseLock();
  }
}
