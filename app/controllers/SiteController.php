<?php
/**
 * Site publico (landing page).
 */
final class SiteController
{
    public function inicio(): void
    {
        if (Auth::logado()) {
            redirect(Auth::academiaId() ? 'painel/dashboard' : 'master/painel');
        }
        view('site/inicio', ['appNome' => Config::get('app')['nome'] ?? 'Academia System']);
    }
}
