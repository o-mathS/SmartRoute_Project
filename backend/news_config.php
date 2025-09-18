<?php
// Arquivo de configuração opcional para buscar notícias de trânsito de uma API externa.
// Defina a URL da API externa se quiser que o endpoint tente atualizar a partir de uma fonte externa
// Exemplo: $EXTERNAL_NEWS_URL = 'https://api.exemplo.com/traffic?apikey=SUA_CHAVE';

// URL do endpoint mock local (padrão). Se você hospedar em outro host/porta, ajuste.
$EXTERNAL_NEWS_URL = 'http://localhost/smartroute/backend/mock_external_news.php';

// Timeout para requisições externas em segundos
$EXTERNAL_TIMEOUT = 5;

// Caminho do arquivo local de cache (relativo a este arquivo)
$LOCAL_CACHE = __DIR__ . '/../assets/traffic_news.json';

// Observação: por segurança o código que faz fetch externo só roda se você definir
// uma URL válida em $EXTERNAL_NEWS_URL. Se você quiser que o servidor tente
// atualizar automaticamente, a aplicação chamará `traffic_news.php?refresh=1`.
