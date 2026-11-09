<?php
/**
 * Anexos do e-mail (09/11/2026): no template de e-mail e no envio Manual, separados da assinatura.
 *
 * Cada arquivo vira um registro imutavel (EmailAnexo) em files/email/anexos/<40 hex>/<nome>; template, mensagem e lembrete
 * apontam para ele, entao o historico mostra o que saiu mesmo se o template mudar depois. files/ nao e servido pela web.
 */
class EmailAnexoService
{
    const PASTA = 'files/email/anexos';

    const MAX_ARQUIVO = 10 * 1024 * 1024;
    const MAX_TOTAL   = 20 * 1024 * 1024;

    const EXTENSOES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip'];

    /**
     * Guarda o arquivo enviado. Usa a transacao aberta.
     *
     * @throws Exception com mensagem para o usuario
     */
    public static function criarDeArquivo(string $caminho, string $nomeOriginal): EmailAnexo
    {
        if (!is_file($caminho))
        {
            throw new Exception('Arquivo do anexo não encontrado. Envie de novo.');
        }

        $nome = self::nomeSeguro($nomeOriginal);
        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (!in_array($ext, self::EXTENSOES, true))
        {
            throw new Exception("Formato de anexo não aceito ({$nomeOriginal}): use PDF, Word, Excel, imagem (JPG, PNG, GIF), TXT ou ZIP.");
        }

        $tamanho = (int) filesize($caminho);
        if ($tamanho > self::MAX_ARQUIVO)
        {
            throw new Exception("O anexo {$nomeOriginal} passa de 10 MB.");
        }

        do
        {
            $pasta = self::PASTA . '/' . bin2hex(random_bytes(20));
        }
        while (file_exists($pasta));

        if (!@mkdir($pasta, 0775, true) || !@copy($caminho, "{$pasta}/{$nome}"))
        {
            throw new Exception('Não foi possível guardar o anexo.');
        }

        $anexo = new EmailAnexo();
        $anexo->nome_original = mb_substr($nomeOriginal, 0, 255);
        $anexo->caminho = "{$pasta}/{$nome}";
        $anexo->tamanho = $tamanho;
        $anexo->system_user_id = TSession::getValue('userid') ?: null;
        $anexo->store();

        return $anexo;
    }

    /** Soma dos anexos de um e-mail ate 20 MB. Usa a transacao aberta. */
    public static function validarTotal(array $ids): void
    {
        $total = 0;
        foreach (self::anexos($ids) as $anexo)
        {
            $total += (int) $anexo->tamanho;
        }

        if ($total > self::MAX_TOTAL)
        {
            throw new Exception('Os anexos passam de 20 MB no total. Tire algum anexo.');
        }
    }

    /** No formato do $attachs do PHPMailerGatway::send: [[caminho absoluto, nome original], ...]. */
    public static function paraEnvio(array $ids): array
    {
        $lista = [];
        foreach (self::anexos($ids) as $anexo)
        {
            $caminho = realpath((string) $anexo->caminho);
            if (!$caminho)
            {
                throw new Exception("O anexo {$anexo->nome_original} não está mais no servidor.");
            }
            $lista[] = [$caminho, (string) $anexo->nome_original];
        }

        return $lista;
    }

    /**
     * Lista dos anexos para a previa e o historico (clipe, nome, tamanho). $link($id) devolve o endereco para abrir.
     */
    public static function listaHtml(array $ids, ?callable $link = null): string
    {
        $itens = '';
        foreach (self::anexos($ids) as $anexo)
        {
            $nome = htmlspecialchars((string) $anexo->nome_original, ENT_QUOTES, 'UTF-8');
            $rotulo = "<i class=\"fas fa-paperclip\"></i> {$nome} <span style=\"color:#777\">(" . self::tamanhoLegivel((int) $anexo->tamanho) . ')</span>';
            $itens .= '<span style="display:inline-block;border:1px solid #ddd;border-radius:4px;padding:3px 8px;margin:0 6px 6px 0;background:#fafafa;">'
                    . ($link ? '<a href="' . htmlspecialchars((string) $link((int) $anexo->id), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $rotulo . '</a>' : $rotulo)
                    . '</span>';
        }

        return $itens === '' ? '' : '<div class="curciol-anexos" style="margin-top:10px;border-top:1px solid #eee;padding-top:8px;">' . $itens . '</div>';
    }

    public static function tamanhoLegivel(int $bytes): string
    {
        if ($bytes >= 1024 * 1024)
        {
            return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /** @return EmailAnexo[] na ordem dos ids */
    private static function anexos(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids)
        {
            return [];
        }

        $porId = [];
        foreach (EmailAnexo::where('id', 'in', $ids)->load() ?: [] as $anexo)
        {
            $porId[(int) $anexo->id] = $anexo;
        }

        return array_values(array_filter(array_map(function ($id) use ($porId) { return $porId[$id] ?? null; }, $ids)));
    }

    /** So o nome do arquivo, sem pastas nem caracteres de risco. */
    private static function nomeSeguro(string $nome): string
    {
        $nome = basename(str_replace('\\', '/', $nome));
        $nome = preg_replace('/[^\p{L}\p{N}._ -]/u', '_', $nome);
        $nome = trim(preg_replace('/\.{2,}/', '.', $nome), ' .');

        return $nome !== '' ? mb_substr($nome, -120) : 'anexo';
    }
}
