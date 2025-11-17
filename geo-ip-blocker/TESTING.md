# Manual Testing Checklist

## Checklist de Testes

### Instalação e Ativação
- [ ] Plugin instala sem erros
- [ ] Plugin ativa sem erros
- [ ] Tabelas do banco de dados são criadas corretamente
- [ ] Opções padrão são configuradas
- [ ] Menu aparece no painel administrativo
- [ ] Verificação de requisitos funciona (PHP 7.4+, WooCommerce)

### Configurações Gerais
- [ ] Ativar/desativar plugin funciona
- [ ] Alternar entre whitelist/blacklist atualiza interface
- [ ] Salvar configurações exibe mensagem de sucesso
- [ ] Configurações são persistidas após salvar
- [ ] Mensagem personalizada é exibida corretamente
- [ ] Seletor de ação de bloqueio funciona (mensagem/redirect/página/403)
- [ ] URL de redirecionamento é validada
- [ ] Seletor de página funciona corretamente

### Bloqueio por País
- [ ] Select2 para seleção de países carrega corretamente
- [ ] Busca de países funciona
- [ ] Múltiplos países podem ser selecionados
- [ ] Países selecionados são salvos
- [ ] Bloqueio por país é aplicado corretamente
- [ ] Whitelist funciona inversamente ao blacklist
- [ ] Países não selecionados não são bloqueados
- [ ] Filtros de continente funcionam (se implementado)

### Bloqueio por IP
- [ ] Adicionar IP individual funciona
- [ ] Adicionar IP range com CIDR (192.168.1.0/24) funciona
- [ ] Adicionar IP range com hífen (192.168.1.1-192.168.1.50) funciona
- [ ] IPv4 é validado corretamente
- [ ] IPv6 é validado corretamente
- [ ] IPs inválidos são rejeitados com mensagem de erro
- [ ] IPs são exibidos na lista após adicionar
- [ ] Remover IP funciona
- [ ] Limpar lista funciona
- [ ] IPs duplicados não são adicionados
- [ ] IPs em range CIDR são bloqueados corretamente
- [ ] IPs fora de range CIDR não são bloqueados

### Exceções
- [ ] Checkbox de "Isentar administradores" funciona
- [ ] Administradores não são bloqueados quando opção ativada
- [ ] Checkbox de "Isentar usuários logados" funciona
- [ ] Usuários logados não são bloqueados quando opção ativada
- [ ] Whitelist de IPs funciona independente de bloqueio de país
- [ ] Exceções por página específica funcionam
- [ ] Bypass para rotas específicas funciona

### WooCommerce
- [ ] Aba WooCommerce aparece nas configurações
- [ ] Enable/disable bloqueio WooCommerce funciona
- [ ] Níveis de bloqueio funcionam:
  - [ ] Site inteiro
  - [ ] Apenas loja
  - [ ] Carrinho e checkout
  - [ ] Apenas checkout
- [ ] Checkboxes de páginas específicas funcionam
- [ ] Metabox aparece na página de edição de produto
- [ ] Checkbox "Ativar restrição geo" no produto funciona
- [ ] Select2 de países no produto funciona
- [ ] Bloqueio a nível de produto funciona
- [ ] Mensagem de produto bloqueado é exibida
- [ ] Produto bloqueado não pode ser adicionado ao carrinho
- [ ] Produto bloqueado é removido automaticamente do carrinho
- [ ] Validação no checkout funciona
- [ ] Mensagem de erro no checkout é exibida

### Logs
- [ ] Tentativas de acesso são registradas
- [ ] Informações corretas são salvas (IP, país, URL, motivo)
- [ ] Tabela de logs é exibida corretamente
- [ ] Paginação funciona
- [ ] Filtros funcionam:
  - [ ] Filtro por data (início)
  - [ ] Filtro por data (fim)
  - [ ] Filtro por país
  - [ ] Filtro por IP
  - [ ] Filtro por motivo
- [ ] Busca funciona
- [ ] Ordenação por coluna funciona
- [ ] Estatísticas são exibidas corretamente:
  - [ ] Total de bloqueios
  - [ ] Bloqueios hoje
  - [ ] Bloqueios últimos 7 dias
  - [ ] Bloqueios últimos 30 dias
- [ ] Exportação CSV funciona
- [ ] CSV exportado contém dados corretos
- [ ] Limite de 50.000 registros é respeitado
- [ ] Bulk actions funcionam:
  - [ ] Deletar selecionados
  - [ ] Select all
- [ ] Botão "Limpar todos os logs" funciona
- [ ] Confirmação é solicitada antes de limpar
- [ ] Limpeza automática de logs antigos funciona
- [ ] Configuração de retenção é respeitada
- [ ] Configuração de máximo de logs funciona

### Ferramentas (Tools)
- [ ] Aba Tools aparece
- [ ] **Teste de Localização de IP:**
  - [ ] Botão "Detectar Minha Localização" funciona
  - [ ] Informações corretas são exibidas (país, região, cidade, ISP)
  - [ ] Status de bloqueio é exibido corretamente
  - [ ] Input de "Testar Outro IP" aceita IPs
  - [ ] Botão "Testar IP" funciona
  - [ ] Resultados para IP testado são exibidos
- [ ] **Gerenciamento de Database:**
  - [ ] Botão "Atualizar Database GeoIP" funciona
  - [ ] Mensagem de sucesso/erro é exibida
  - [ ] Database é atualizado
  - [ ] Botão "Limpar Cache" funciona
  - [ ] Cache é limpo corretamente
  - [ ] Toggle de atualização automática funciona
- [ ] **Import/Export:**
  - [ ] Botão "Exportar Configurações" funciona
  - [ ] Arquivo JSON é baixado
  - [ ] JSON contém configurações corretas
  - [ ] Nome do arquivo tem timestamp
  - [ ] Input de "Importar Configurações" aceita arquivo
  - [ ] Validação de arquivo funciona (apenas JSON)
  - [ ] Confirmação é solicitada antes de importar
  - [ ] Importação sobrescreve configurações
  - [ ] Página recarrega após importação
  - [ ] Botão "Resetar Configurações" funciona
  - [ ] Confirmação é solicitada
  - [ ] Configurações voltam aos valores padrão
- [ ] **Presets de Países:**
  - [ ] Busca de países funciona
  - [ ] Presets regionais funcionam:
    - [ ] Europa
    - [ ] América do Norte
    - [ ] América do Sul
    - [ ] Ásia
    - [ ] África
    - [ ] Oceania
  - [ ] Botão "Selecionar Todos" funciona
  - [ ] Botão "Limpar Seleção" funciona
- [ ] **Informações do Sistema:**
  - [ ] Informações são exibidas corretamente
  - [ ] Versão do WordPress
  - [ ] Versão do PHP
  - [ ] Versão do MySQL
  - [ ] Versão do WooCommerce
  - [ ] Versão do plugin
  - [ ] Limite de memória
  - [ ] Botão "Copiar para Clipboard" funciona
  - [ ] Toggle de modo debug funciona
  - [ ] Botão "Ver Log de Debug" funciona
  - [ ] Conteúdo do log é exibido
  - [ ] Botão "Limpar Log" funciona
  - [ ] Confirmação é solicitada

### Frontend - Mensagens de Bloqueio
- [ ] Mensagem de bloqueio é exibida quando acesso é negado
- [ ] Template padrão é exibido corretamente
- [ ] Template "minimal" funciona
- [ ] Template "dark" funciona
- [ ] Shortcodes funcionam:
  - [ ] [geo_blocker_ip]
  - [ ] [geo_blocker_country]
  - [ ] [geo_blocker_country_code]
  - [ ] [geo_blocker_reason]
  - [ ] [geo_blocker_date]
  - [ ] [geo_blocker_site_name]
  - [ ] [geo_blocker_site_url]
- [ ] Mensagem personalizada é exibida
- [ ] Detalhes (IP, país) são exibidos quando configurado
- [ ] Detalhes são ocultados quando configurado
- [ ] URL de contato é exibida quando configurada
- [ ] Botão de contato funciona
- [ ] Template responde corretamente (mobile)
- [ ] Status HTTP 403 é retornado
- [ ] Headers de nocache são enviados
- [ ] Override de template do tema funciona
- [ ] Estilos do tema são carregados quando configurado
- [ ] "Powered by" é exibido quando configurado

### Performance e Cache
- [ ] Plugin não causa lentidão perceptível
- [ ] Consultas ao banco otimizadas
- [ ] Cache de geolocalização funciona
- [ ] Cache expira corretamente
- [ ] Cache de países funciona
- [ ] Cache de configurações funciona
- [ ] Integração com plugins de cache funciona:
  - [ ] WP Rocket
  - [ ] W3 Total Cache
  - [ ] WP Super Cache
  - [ ] LiteSpeed Cache
- [ ] Limpar cache limpa todos os caches
- [ ] Atualizar configurações limpa cache automaticamente
- [ ] Warm-up de cache funciona

### Segurança
- [ ] Nonces são verificados em todas as ações AJAX
- [ ] Permissões são verificadas (manage_options)
- [ ] IPs são validados antes de salvar
- [ ] Códigos de país são validados
- [ ] HTML é sanitizado (mensagens)
- [ ] URLs são sanitizadas
- [ ] SQL injection é prevenido (prepared statements)
- [ ] XSS é prevenido (escaping correto)
- [ ] CSRF é prevenido (nonces)
- [ ] Path traversal é prevenido
- [ ] Rate limiting funciona para:
  - [ ] Chamadas de API
  - [ ] Lookups de geolocalização
  - [ ] Atualizações de configurações
  - [ ] Export
  - [ ] Import

### Rate Limiting
- [ ] Limite de chamadas API é respeitado
- [ ] Limite de lookups é respeitado
- [ ] Limite de updates é respeitado
- [ ] Mensagem de "rate limited" é exibida
- [ ] Reset de rate limit funciona após período
- [ ] Identificação por IP funciona
- [ ] Identificação por usuário funciona

### Database
- [ ] Indexes são criados corretamente
- [ ] Queries são otimizadas (EXPLAIN)
- [ ] Optimize tables funciona
- [ ] Analyze tables funciona
- [ ] Estatísticas de tabela são exibidas
- [ ] Migração de schema funciona
- [ ] Upgrade de database é automático

### Compatibilidade
- [ ] Plugin funciona com tema padrão (Twenty Twenty-Four)
- [ ] Plugin funciona com temas populares
- [ ] Plugin funciona com WooCommerce 6.0+
- [ ] Plugin funciona com WooCommerce 8.0+
- [ ] Plugin funciona com WordPress 5.8+
- [ ] Plugin funciona com WordPress 6.4+
- [ ] Plugin funciona com PHP 7.4
- [ ] Plugin funciona com PHP 8.0
- [ ] Plugin funciona com PHP 8.1
- [ ] Plugin funciona com PHP 8.2
- [ ] Plugin funciona com MySQL 5.7
- [ ] Plugin funciona com MySQL 8.0
- [ ] Plugin funciona com MariaDB

### Desinstalação
- [ ] Desativar plugin não remove dados
- [ ] Desinstalar plugin remove tabelas (se configurado)
- [ ] Desinstalar plugin remove opções (se configurado)
- [ ] Não há erros durante desinstalação

### Edge Cases
- [ ] IP não detectado é tratado corretamente
- [ ] País não detectado é tratado corretamente
- [ ] API de geolocalização offline é tratada
- [ ] Database corrompido é tratado
- [ ] Configurações inválidas são corrigidas
- [ ] Lista de IPs vazia não causa erro
- [ ] Lista de países vazia não causa erro
- [ ] Sem WooCommerce instalado não causa erro fatal

### Testes de Regressão
- [ ] Atualização do plugin não quebra funcionalidades
- [ ] Configurações antigas são migradas corretamente
- [ ] Database antigo é migrado corretamente
- [ ] Não há conflitos com outras versões

## Casos de Teste Específicos

### Teste 1: Bloqueio por País
1. Configurar modo "blacklist"
2. Adicionar "US" aos países bloqueados
3. Simular acesso de IP dos EUA
4. Verificar que acesso é bloqueado
5. Verificar que log é criado

### Teste 2: Whitelist de IPs
1. Configurar bloqueio de país "US"
2. Adicionar IP específico à whitelist
3. Simular acesso desse IP dos EUA
4. Verificar que acesso é permitido

### Teste 3: Range CIDR
1. Adicionar "192.168.1.0/24" à blacklist
2. Simular acesso de "192.168.1.100"
3. Verificar bloqueio
4. Simular acesso de "192.168.2.1"
5. Verificar que é permitido

### Teste 4: Produto WooCommerce Bloqueado
1. Criar produto
2. Ativar restrição geo no produto
3. Selecionar país "BR"
4. Simular acesso de usuário brasileiro
5. Verificar que produto não pode ser comprado
6. Verificar mensagem de bloqueio

### Teste 5: Exemption de Administrador
1. Configurar bloqueio de país "US"
2. Ativar "Isentar administradores"
3. Logar como administrador dos EUA
4. Verificar que acesso é permitido

### Teste 6: Rate Limiting
1. Fazer 100+ chamadas de API em 1 hora
2. Verificar que 101ª chamada é bloqueada
3. Aguardar reset
4. Verificar que chamadas voltam a funcionar

### Teste 7: Cache
1. Fazer lookup de IP
2. Verificar que resultado é cacheado
3. Fazer mesmo lookup novamente
4. Verificar que cache é usado (mais rápido)
5. Limpar cache
6. Verificar que nova lookup é feita

### Teste 8: Export/Import
1. Configurar plugin completamente
2. Exportar configurações
3. Resetar configurações
4. Importar arquivo exportado
5. Verificar que todas configurações voltaram
