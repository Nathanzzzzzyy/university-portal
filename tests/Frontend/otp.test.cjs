const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const source = readFileSync('public/js/otp.js', 'utf8');
function setup(seconds = 0) {
    let focused = -1;
    const inputs = Array.from({ length: 4 }, (_, index) => ({
        value: '', events: {}, attributes: {},
        addEventListener(name, callback) { this.events[name] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { focused = index; }, select() {},
    }));
    const nodes = { 'otp-form': { querySelectorAll: () => inputs }, 'otp-error': { textContent: '' }, 'otp-resend': { dataset: { seconds } }, 'resend-status': { textContent: '' } };
    vm.runInNewContext(source, { document: { getElementById: id => nodes[id] }, setInterval: () => 1, clearInterval() {}, Date });
    return { inputs, nodes, focused: () => focused };
}
test('entering a digit advances and removes invalid characters', () => {
    const ui = setup();
    ui.inputs[0].value = '5'; ui.inputs[0].events.input();
    assert.equal(ui.focused(), 1);
    ui.inputs[1].value = 'x'; ui.inputs[1].events.input();
    assert.equal(ui.inputs[1].value, '');
});
test('pasting four digits fills every box and keeps leading zero', () => {
    const ui = setup(); let prevented = false;
    ui.inputs[2].events.paste({ preventDefault() { prevented = true; }, clipboardData: { getData: () => '0017' } });
    assert.equal(prevented, true);
    assert.equal(ui.inputs.map(input => input.value).join(''), '0017');
    assert.equal(ui.focused(), 3);
});
test('backspace from empty box clears and focuses previous digit', () => {
    const ui = setup(); ui.inputs[0].value = '1';
    ui.inputs[1].events.keydown({ key: 'Backspace', preventDefault() {} });
    assert.equal(ui.inputs[0].value, ''); assert.equal(ui.focused(), 0);
});
test('one-time-code autofill distributes four digits', () => {
    const ui = setup(); let prevented = false;
    ui.inputs[0].events.beforeinput({ data: '1234', preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(ui.inputs.map(input => input.value).join(''), '1234');
});
test('resend is disabled during server cooldown and enabled afterwards', () => {
    const pending = setup(60); assert.equal(pending.nodes['otp-resend'].disabled, true);
    const ready = setup(0); assert.equal(ready.nodes['otp-resend'].disabled, false);
});
