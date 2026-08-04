<?php

namespace Daniel\Origins\Tester;

class TestReportGenerator
{
    public function generate(array $results): string
    {
        $total   = count($results);
        $passed  = $this->countByStatus($results, TestResult::PASSED);
        $failed  = $this->countByStatus($results, TestResult::FAILED);
        $errors  = $this->countByStatus($results, TestResult::ERROR);
        $skipped = $this->countByStatus($results, TestResult::SKIPPED);
        $totalMs = 0.0;
        foreach ($results as $r) {
            $totalMs += $r->durationMs;
        }

        $generatedAt = date('Y-m-d H:i:s');
        $rows = $this->renderRows($results);
        $css  = $this->css();

        $allGreen = ($failed === 0 && $errors === 0);
        $bannerClass = $allGreen ? 'ok' : 'ko';
        $bannerText  = $allGreen
            ? "Todos os testes passaram"
            : "Existem testes com falha";

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Testes - Origins</title>
    <style>{$css}</style>
</head>
<body>
    <div class="wrap">
        <h1>Relatório de Testes <span class="muted">Origins</span></h1>
        <p class="muted">Gerado em {$generatedAt}</p>

        <div class="banner {$bannerClass}">{$bannerText}</div>

        <div class="cards">
            <div class="card total"><span class="num">{$total}</span><span class="lbl">Total</span></div>
            <div class="card passed"><span class="num">{$passed}</span><span class="lbl">Passou</span></div>
            <div class="card failed"><span class="num">{$failed}</span><span class="lbl">Falhou</span></div>
            <div class="card error"><span class="num">{$errors}</span><span class="lbl">Erro</span></div>
            <div class="card skipped"><span class="num">{$skipped}</span><span class="lbl">Ignorado</span></div>
            <div class="card time"><span class="num">{$this->formatMs($totalMs)}</span><span class="lbl">Tempo</span></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Teste</th>
                    <th>Classe</th>
                    <th class="right">Tempo</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
    }

    private function renderRows(array $results): string
    {
        if (empty($results)) {
            return '<tr><td colspan="4" class="muted center">Nenhum teste encontrado.</td></tr>';
        }

        $html = '';
        foreach ($results as $r) {
            $badge = $this->statusBadge($r->status);
            $name  = $this->e($r->displayName !== '' ? $r->displayName : $r->method);
            $method = $this->e($r->method);
            $class = $this->e($r->class);
            $time  = $this->formatMs($r->durationMs);

            $detail = '';
            if ($r->message !== null && $r->message !== '') {
                $trace = ($r->trace !== null && $r->trace !== '')
                    ? '<pre class="trace">' . $this->e($r->trace) . '</pre>'
                    : '';
                $detail = '<div class="detail"><div class="msg">' . $this->e($r->message) . '</div>' . $trace . '</div>';
            }

            $html .= <<<ROW
                <tr class="row-{$r->status}">
                    <td>{$badge}</td>
                    <td><div class="tname">{$name}</div><div class="muted small">{$method}</div>{$detail}</td>
                    <td class="muted">{$class}</td>
                    <td class="right">{$time}</td>
                </tr>
            ROW;
        }

        return $html;
    }

    private function statusBadge(string $status): string
    {
        $label = match ($status) {
            TestResult::PASSED  => 'PASSOU',
            TestResult::FAILED  => 'FALHOU',
            TestResult::ERROR   => 'ERRO',
            TestResult::SKIPPED => 'IGNORADO',
            default             => strtoupper($status),
        };
        return '<span class="badge badge-' . $status . '">' . $label . '</span>';
    }

    private function countByStatus(array $results, string $status): int
    {
        $count = 0;
        foreach ($results as $r) {
            if ($r->status === $status) {
                $count++;
            }
        }
        return $count;
    }

    private function formatMs(float $ms): string
    {
        return number_format($ms, 2, ',', '.') . ' ms';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function css(): string
    {
        return <<<CSS
            * { box-sizing: border-box; }
            body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 32px 16px; }
            .wrap { max-width: 960px; margin: 0 auto; }
            h1 { font-size: 1.6rem; margin: 0 0 4px; }
            .muted { color: #94a3b8; }
            .small { font-size: 0.8rem; }
            .center { text-align: center; }
            .right { text-align: right; white-space: nowrap; }
            .banner { margin: 20px 0; padding: 12px 16px; border-radius: 10px; font-weight: 600; }
            .banner.ok { background: #052e1a; color: #4ade80; border: 1px solid #14532d; }
            .banner.ko { background: #2e0505; color: #f87171; border: 1px solid #7f1d1d; }
            .cards { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
            .card { flex: 1 1 120px; background: #1e293b; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 4px; }
            .card .num { font-size: 1.5rem; font-weight: 700; }
            .card .lbl { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; }
            .card.passed .num { color: #4ade80; }
            .card.failed .num { color: #f87171; }
            .card.error .num { color: #fb923c; }
            .card.skipped .num { color: #94a3b8; }
            table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 10px; overflow: hidden; }
            th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid #334155; vertical-align: top; }
            th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; background: #0f172a; }
            tr:last-child td { border-bottom: none; }
            .tname { font-weight: 600; }
            .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; letter-spacing: .03em; }
            .badge-passed { background: #052e1a; color: #4ade80; }
            .badge-failed { background: #2e0505; color: #f87171; }
            .badge-error { background: #2e1605; color: #fb923c; }
            .badge-skipped { background: #1e293b; color: #94a3b8; border: 1px solid #334155; }
            .detail { margin-top: 8px; padding: 10px; background: #0f172a; border-radius: 8px; border-left: 3px solid #f87171; }
            .detail .msg { color: #fecaca; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap; }
            .trace { margin: 8px 0 0; color: #64748b; font-size: 0.75rem; overflow-x: auto; white-space: pre; }
        CSS;
    }
}
