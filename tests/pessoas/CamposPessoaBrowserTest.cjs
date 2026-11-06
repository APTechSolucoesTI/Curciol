// Nao salva registros. Credenciais em JSON temporario externo via CURCIOL_TEST_CREDENTIALS.
const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('node:assert/strict');
(async () => {
    const c = JSON.parse(fs.readFileSync(process.env.CURCIOL_TEST_CREDENTIALS, 'utf8'));
    const b = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const p = await b.newPage({ ignoreHTTPSErrors: true });
        const erros = []; p.on('pageerror', e => erros.push(e.message));
        await p.goto('https://curciol1.sislawyer.com.br:90/login-adm');
        await p.locator('[name=login]').fill(c.login); await p.locator('[name=login]').press('Tab');
        await p.waitForFunction(() => Array.from(document.querySelector('select[name=unit_id]')?.options || []).some(o => o.value));
        await p.locator('[name=password]').fill(c.senha);
        await p.locator('[name=unit_id]').selectOption(await p.locator('[name=unit_id]').evaluate(e => Array.from(e.options).find(o => o.value).value));
        await p.locator('#tbutton_btn_login').click();
        await p.waitForURL(/class=AgendamentosFilterForm/); await p.waitForLoadState('networkidle');
        for (const classe of ['ClienteForm', 'FornecedorForm', 'ContraparteForm', 'RepresentanteLegalForm', 'ProfissionalForm', 'ParceiroForm']) {
            console.log('Verificando', classe);
            await p.evaluate(cl => __adianti_load_page('index.php?class=' + cl + '&method=onShow'), classe);
            const raiz = p.locator('#adianti_right_panel > [page_name]').last();
            await p.waitForFunction(cl => Array.from(document.querySelectorAll('#adianti_right_panel > [page_name]')).at(-1)?.getAttribute('page_name') === cl, classe);
            const telefone = raiz.locator('input[name=telefone]');
            await telefone.fill(''); await telefone.pressSequentially('19912345678', { delay: 60 });
            assert.equal((await telefone.inputValue()).replace(/\D/g, ''), '19912345678', classe + ': numero inteiro');
            await telefone.press('ControlOrMeta+A'); await telefone.press('Backspace');
            await telefone.pressSequentially('1934567890', { delay: 50 });
            assert.equal(await telefone.inputValue(), '(19) 3456-7890', classe + ': telefone fixo');
            await telefone.press('ControlOrMeta+A'); await telefone.press('Backspace');
            await telefone.pressSequentially('19987654321', { delay: 50 });
            assert.equal(await telefone.inputValue(), '(19) 98765-4321', classe + ': celular redigitado');
            await telefone.fill('+55 19 98765-4321');
            assert.equal(await telefone.inputValue(), '(19) 98765-4321', classe + ': celular colado com DDI');
            await raiz.locator('input[name=email]').pressSequentially('TESTE@EXEMPLO.INVALID', { delay: 20 });
            assert.equal(await raiz.locator('input[name=email]').inputValue(), 'teste@exemplo.invalid');
            await raiz.getByText('Contatos', { exact: true }).click();
            await raiz.locator('input[name=pessoa_contato_pessoa_telefone]').pressSequentially('19912345679', { delay: 50 });
            await raiz.locator('input[name=pessoa_contato_pessoa_email]').pressSequentially('CONTATO@EXEMPLO.INVALID', { delay: 20 });
            assert.equal(await raiz.locator('input[name=pessoa_contato_pessoa_telefone]').inputValue(), '(19) 91234-5679');
            await p.evaluate(() => Template.closeRightPanel());
            await p.waitForFunction(() => document.querySelectorAll('#adianti_right_panel > [page_name]').length === 0);
        }
        assert.deepEqual(erros, [], 'digitar e reabrir formularios nao gera erro JavaScript');
        console.log('TUDO OK: telefone/e-mail nos seis formularios, sem salvar dados.');
    } finally { await b.close(); }
})().catch(e => { console.error(e.message); process.exitCode = 1; });
