// Executar com Playwright disponivel e CURCIOL_TEST_CREDENTIALS apontando para JSON temporario fora do repositorio.
// Nao salva pessoa nem contato no banco: testa somente a grade em rascunho.
const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('node:assert/strict');

(async () => {
    const credenciais = JSON.parse(fs.readFileSync(process.env.CURCIOL_TEST_CREDENTIALS, 'utf8'));
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ ignoreHTTPSErrors: true });
        const base = 'https://curciol1.sislawyer.com.br:90/';
        await page.goto(base + 'login-adm');
        await page.locator('input[name=login]').fill(credenciais.login);
        await page.locator('input[name=login]').press('Tab');
        await page.waitForFunction(() => Array.from(document.querySelector('select[name=unit_id]')?.options || []).some(o => o.value));
        await page.locator('input[name=password]').fill(credenciais.senha);
        const unidade = await page.locator('select[name=unit_id]').evaluate(e => Array.from(e.options).find(o => o.value).value);
        await page.locator('select[name=unit_id]').selectOption(unidade);
        await page.locator('#tbutton_btn_login').click();
        await page.waitForURL(/class=AgendamentosFilterForm/);
        await page.waitForLoadState('networkidle');
        await page.evaluate(() => __adianti_load_page('index.php?class=ClienteForm&method=onShow'));
        const cliente = page.locator('#adianti_right_panel [page_name=ClienteForm]');
        await cliente.locator('input[name=nome]').fill('TESTE CLIENTE RASCUNHO');
        await cliente.locator('select[name=tipo_pessoa_id]').selectOption('2');
        await cliente.getByText('Contatos', { exact: true }).click();
        await cliente.locator('input[name=pessoa_contato_pessoa_nome]').fill('CONTATO CLIENTE');
        await cliente.locator('input[name=pessoa_contato_pessoa_email]').fill('cliente-rascunho@exemplo.invalid');
        await cliente.locator('[id="64be92cf7046f"]').click();
        await page.waitForFunction(() => document.querySelectorAll('[page_name=ClienteForm] [id=pessoa_contato_pessoa_list] tbody tr').length === 1);
        await cliente.getByText('Representantes legais', { exact: true }).click();
        await cliente.getByRole('button', { name: 'Novo', exact: true }).click();
        const representante = page.locator('#adianti_right_panel [page_name=RepresentanteLegalForm]');
        await representante.locator('input[name=nome]').fill('TESTE REPRESENTANTE RASCUNHO');
        await representante.getByText('Contatos', { exact: true }).click();
        await representante.locator('input[name=pessoa_contato_pessoa_nome]').fill('CONTATO REPRESENTANTE');
        await representante.locator('input[name=pessoa_contato_pessoa_email]').fill('representante-rascunho@exemplo.invalid');
        const resposta = page.waitForResponse(r => r.url().includes('method=onAddDetailPessoaContatoPessoa'));
        await representante.locator('[id="64be92cf7046f"]').click();
        await resposta;
        await page.waitForTimeout(500);
        const linhasCliente = await cliente.locator('[id=pessoa_contato_pessoa_list] tbody tr').allTextContents();
        const linhasRepresentante = await representante.locator('[id=pessoa_contato_pessoa_list] tbody tr').allTextContents();
        assert.equal(linhasCliente.length, 1, 'Adicionar no representante nao altera a grade do cliente');
        assert.equal(linhasRepresentante.length, 1, 'Adicionar preenche a grade do representante');
        assert.match(linhasCliente[0], /CONTATO CLIENTE/);
        assert.match(linhasRepresentante[0], /CONTATO REPRESENTANTE/);
        assert.equal(await cliente.locator('input[name=nome]').inputValue(), 'TESTE CLIENTE RASCUNHO');
        assert.equal(await representante.locator('input[name=nome]').inputValue(), 'TESTE REPRESENTANTE RASCUNHO');
        console.log('TUDO OK: cortinas e contatos independentes; nenhum cadastro salvo.');
    } finally { await browser.close(); }
})().catch(e => { console.error(e.message); process.exitCode = 1; });
