import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import createDOMPurify from 'dompurify';
import { sanitizeHtml } from '../../resources/vue/helpers/safe-html.mjs';

const window = new JSDOM('').window;
const purifier = createDOMPurify(window);
const samples = [
    '<img src=x onerror="alert(1)"><script>alert(1)</script>',
    '<a href="javascript:alert(1)">Open</a><iframe src="https://example.test"></iframe>',
    '<svg><g onload="alert(1)"></g></svg><math><mtext><img src=x onerror="alert(1)"></mtext></math>',
];
for (const dirty of samples) {
    const fragment = window.document.createElement('div');
    fragment.innerHTML = sanitizeHtml(dirty, purifier);
    assert.equal(fragment.querySelector('script,iframe,object,embed,svg,math'), null);
    for (const node of fragment.querySelectorAll('*')) {
        for (const attribute of node.attributes) {
            assert.equal(/^on/i.test(attribute.name), false);
            assert.equal(/^\s*javascript:/i.test(attribute.value), false);
        }
    }
}
const original = '<p style="color: red;">Accents é ñ € — 001234 <strong>Texte</strong></p><table><tbody><tr><td>1234.56</td></tr></tbody></table>';
assert.equal(sanitizeHtml(original, purifier), original);
assert.equal(sanitizeHtml(null, purifier), '');
window.close();
console.log('HTML rendering: injection payloads rejected; rich text, tables and accents preserved.');
