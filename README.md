facebook2trakt
==============
Pequeno script que faz scrobble para o Trakt à partir das atividades do Facebook (configurado para pegar somente séries do netflix por enquanto)

Uso
---
1. Levante um servidor HTTP local e crie um link (`ln -s`) para a pasta do repositório. Durante a versão beta é obrigatório que seja `http://localhost:8888/seu-diretorio`.
2. Copie o arquivo `config-sample.php`, salve como `config.php` e edite com suas informações
  1. HTTP_ROOT: URL configurada no item 1
  2. TRAKT_USERNAME: Seu nome de usuário no trakt.com
  3. TRAKT_PASSWORD: Sua senha no trakt.com
  4. TRAKT_API_KEY: A sua chave de API no trakt.com. Para descobrir vá em settings>api
3. Gere um Token do Facebook entrando na url configurada no item 1 `/autenticar.php`
4. No console, execute `./scrobble.php`
5. Adicione o `scrobble.php` no CRON ou Launch Agent.

