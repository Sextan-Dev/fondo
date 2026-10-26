import JSZip from 'jszip';

// Smallest .xlsx Excel opens without complaint: one sheet, text and numbers, a bold
// style and a currency style, so amounts stay numbers people can add up.
//
// rows: arrays of cells. A cell is a string, a number, null (empty) or
// { value, style } with style 'bold', 'money' or 'boldMoney'.
// options: { widths: [column widths in characters], freeze: rows to keep on top }

const STYLES = { bold: 1, money: 2, boldMoney: 3 };

const escape = value => String(value)
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const column = index => {
    let name = '';
    for (index++; index > 0; index = Math.floor((index - 1) / 26)) {
        name = String.fromCharCode(65 + (index - 1) % 26) + name;
    }
    return name;
};

function cellXml(cell, ref) {
    let value = cell !== null && typeof cell === 'object' ? cell.value : cell;
    let style = cell !== null && typeof cell === 'object' && STYLES[cell.style] ? ` s="${STYLES[cell.style]}"` : '';
    if (value === null || value === undefined || value === '') {
        return style ? `<c r="${ref}"${style}/>` : '';
    }
    if (typeof value === 'number' && isFinite(value)) {
        return `<c r="${ref}"${style}><v>${value}</v></c>`;
    }
    return `<c r="${ref}"${style} t="inlineStr"><is><t xml:space="preserve">${escape(value)}</t></is></c>`;
}

function sheetXml(rows, options) {
    let freeze = options.freeze || 0;
    let views = freeze
        ? `<sheetViews><sheetView workbookViewId="0"><pane ySplit="${freeze}" topLeftCell="A${freeze + 1}" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>`
        : '';
    let cols = (options.widths || []).length
        ? '<cols>' + options.widths.map((width, i) => `<col min="${i + 1}" max="${i + 1}" width="${width}" customWidth="1"/>`).join('') + '</cols>'
        : '';
    let data = rows.map((row, r) =>
        `<row r="${r + 1}">` + row.map((cell, c) => cellXml(cell, column(c) + (r + 1))).join('') + '</row>'
    ).join('');
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
        views + cols + `<sheetData>${data}</sheetData></worksheet>`;
}

const STYLES_XML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
    '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;$&quot;#,##0.00;-&quot;$&quot;#,##0.00"/></numFmts>' +
    '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>' +
    '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>' +
    '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' +
    '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
    '<cellXfs count="4">' +
    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
    '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' +
    '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>' +
    '<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>' +
    '</cellXfs>' +
    '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
    '</styleSheet>';

export async function downloadXlsx(filename, sheetName, rows, options = {}) {
    let zip = new JSZip();
    zip.file('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
        '<Default Extension="xml" ContentType="application/xml"/>' +
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
        '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>');
    zip.file('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>');
    zip.file('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
        // Excel refuses sheet names over 31 characters or with []:*?/\
        `<sheets><sheet name="${escape(String(sheetName).replace(/[[\]:*?/\\]/g, ' ').slice(0, 31))}" sheetId="1" r:id="rId1"/></sheets></workbook>`);
    zip.file('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>');
    zip.file('xl/styles.xml', STYLES_XML);
    zip.file('xl/worksheets/sheet1.xml', sheetXml(rows, options));

    let blob = await zip.generateAsync({
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    });
    let url = URL.createObjectURL(blob);
    let link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
