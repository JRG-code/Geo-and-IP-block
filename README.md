# Geo & IP Blocker for WooCommerce

[![WordPress Version](https://img.shields.io/badge/WordPress-%3E%3D5.8-blue.svg)](https://wordpress.org/)
[![WooCommerce Version](https://img.shields.io/badge/WooCommerce-%3E%3D6.0-purple.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-green.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Plugin completo para WordPress e WooCommerce que permite bloquear ou permitir acesso ao seu site com base em geolocalização (país/região) ou endereços IP.

## 📋 Índice

- [Recursos](#-recursos)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração Inicial](#-configuração-inicial)
- [Uso](#-uso)
- [Hooks para Desenvolvedores](#-hooks-para-desenvolvedores)
- [API](#-api)
- [Performance](#-performance)
- [Segurança](#-segurança)
- [Troubleshooting](#-troubleshooting)
- [Testes](#-testes)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

## ✨ Recursos

### Bloqueio Geográfico
- Bloqueio ou permissão por país (lista completa de 250+ países)
- Suporte a whitelist (apenas países selecionados) ou blacklist (bloquear países selecionados)
- Geolocalização precisa usando MaxMind GeoIP2 ou IP-API
- Cache de consultas de geolocalização para performance

### Bloqueio por IP
- Bloqueio de IPs individuais
- Suporte a CIDR notation (`192.168.1.0/24`)
- Suporte a ranges (`192.168.1.1-192.168.1.50`)
- Suporte completo a IPv4 e IPv6
- Whitelist e blacklist de IPs

### Integração WooCommerce
- Bloqueio a nível de site inteiro ou apenas loja
- Bloqueio específico de carrinho/checkout
- Restrição por produto individual
- Restrição por categoria de produto
- Mensagens personalizadas para produtos bloqueados
- Remoção automática de produtos bloqueados do carrinho

### Logs e Estatísticas
- Registro completo de tentativas de acesso
- Filtros avançados (data, país, IP, motivo)
- Estatísticas em tempo real
- Exportação CSV (até 50.000 registros)
- Gráficos com Chart.js (timeline, países, motivos)
- Limpeza automática de logs antigos

### Templates Personalizáveis
- 3 templates prontos (default, minimal, dark)
- Suporte a override de template pelo tema
- 7 shortcodes para conteúdo dinâmico
- Totalmente responsivo
- Acessibilidade (WCAG 2.1)

### Ferramentas
- Teste de geolocalização de IP
- Atualização manual de database GeoIP
- Import/Export de configurações (JSON)
- Reset para configurações padrão
- Presets de países por continente
- Debug mode com visualização de logs

### Performance
- Cache multi-camadas (object cache + transients)
- Compatibilidade com plugins de cache populares
- Indexes otimizados no banco de dados
- Rate limiting para proteção de API
- Lazy loading quando possível

### Segurança
- Validação completa de inputs
- Nonce verification em todas as ações
- Prepared statements (prevenção de SQL injection)
- Sanitização de HTML (prevenção de XSS)
- CSRF protection
- Rate limiting
- Path traversal prevention

## 📦 Requisitos

### Mínimos
- WordPress 5.8 ou superior
- WooCommerce 6.0 ou superior
- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.2 ou superior
- Extensão PHP `curl` para APIs de geolocalização
- Extensão PHP `json`

### Recomendados
- WordPress 6.4+
- WooCommerce 8.0+
- PHP 8.1+
- MySQL 8.0+ ou MariaDB 10.6+
- Servidor com pelo menos 128MB de memória RAM
- Object cache (Redis/Memcached) para alta performance
- SSL/HTTPS configurado

## 🚀 Instalação

### Via WordPress Admin

1. Acesse **Plugins > Adicionar Novo**
2. Procure por "Geo & IP Blocker"
3. Clique em "Instalar Agora"
4. Após instalação, clique em "Ativar"

### Via Upload Manual

1. Faça download do plugin
2. Acesse **Plugins > Adicionar Novo > Enviar Plugin**
3. Selecione o arquivo `.zip` e clique em "Instalar Agora"
4. Após instalação, clique em "Ativar"

### Via FTP

1. Faça download e descompacte o plugin
2. Envie a pasta `geo-ip-blocker` para `/wp-content/plugins/`
3. Acesse **Plugins** no painel do WordPress
4. Ative o plugin

### Via WP-CLI

```bash
wp plugin install geo-ip-blocker --activate
```

### Pós-Instalação

Após ativação, o plugin:
- Cria tabelas no banco de dados automaticamente
- Configura opções padrão
- Adiciona menu "Geo & IP Blocker" no admin

## ⚙️ Configuração Inicial

### 1. Escolher Provedor de Geolocalização

#### MaxMind GeoIP2 (Recomendado)

1. Registre-se em: https://www.maxmind.com/en/geolite2/signup
2. Gere uma chave de licença
3. No plugin, vá em **Configurações > API de Geolocalização**
4. Selecione "MaxMind GeoIP2"
5. Cole sua chave de API
6. Clique em "Testar Conexão"
7. Salve as configurações

#### IP-API (Gratuita, limite de 45 req/min)

1. Vá em **Configurações > API de Geolocalização**
2. Selecione "IP-API"
3. Salve as configurações

**Nota:** IP-API tem limite de 45 requisições por minuto. Para sites com alto tráfego, use MaxMind.

### 2. Configurar Modo de Bloqueio

#### Modo Blacklist (Bloquear Países Selecionados)

1. Vá em **Configurações > Geral**
2. Em "Modo de Bloqueio", selecione "Blacklist"
3. Em "Países Bloqueados", selecione os países que deseja bloquear
4. Salve as configurações

Exemplo: Bloquear apenas Rússia e China
- Modo: Blacklist
- Países bloqueados: RU, CN

#### Modo Whitelist (Permitir Apenas Países Selecionados)

1. Vá em **Configurações > Geral**
2. Em "Modo de Bloqueio", selecione "Whitelist"
3. Em "Países Permitidos", selecione os países permitidos
4. Salve as configurações

Exemplo: Permitir apenas Brasil e Portugal
- Modo: Whitelist
- Países permitidos: BR, PT

### 3. Configurar Ação de Bloqueio

Escolha o que acontece quando um visitante é bloqueado:

- **Mensagem**: Exibe página com mensagem personalizada
- **Redirecionamento**: Redireciona para URL específica
- **Página WordPress**: Redireciona para página do WordPress
- **HTTP 403**: Retorna erro 403 Forbidden

### 4. Adicionar IPs à Whitelist/Blacklist (Opcional)

Para bloquear ou permitir IPs específicos:

1. Vá em **Configurações > IPs**
2. Adicione IPs à whitelist ou blacklist

Formatos suportados:
```
192.168.1.1              # IP individual
192.168.1.0/24           # CIDR notation
192.168.1.1-192.168.1.50 # Range com hífen
2001:db8::1              # IPv6
2001:db8::/32            # IPv6 CIDR
```

## 📖 Uso

### Bloqueio Básico por País

```php
// No painel administrativo:
// 1. Ir em "Geo & IP Blocker" > "Configurações"
// 2. Ativar plugin
// 3. Selecionar modo "Blacklist"
// 4. Adicionar países à lista de bloqueados
// 5. Salvar
```

### Bloqueio de Produto WooCommerce

1. Edite um produto
2. Role até o metabox "Geo Restrictions"
3. Marque "Ativar restrições geográficas"
4. Selecione os países que NÃO podem comprar
5. Publique/atualize o produto

### Usar Templates Personalizados

Copie o template padrão para seu tema:

```bash
# Copiar template
cp wp-content/plugins/geo-ip-blocker/templates/blocked-message.php \
   wp-content/themes/seu-tema/geo-blocker/blocked-message.php
```

Edite o arquivo no seu tema para personalizar.

### Shortcodes nas Mensagens

Use shortcodes para conteúdo dinâmico:

```
Seu IP: [geo_blocker_ip]
Seu país: [geo_blocker_country]
Código do país: [geo_blocker_country_code]
Motivo: [geo_blocker_reason]
Data: [geo_blocker_date]
Nome do site: [geo_blocker_site_name]
URL do site: [geo_blocker_site_url]
```

## 🔌 Hooks para Desenvolvedores

### Filters

#### `geo_blocker_should_block`

Modifica a decisão de bloqueio.

```php
/**
 * @param bool   $should_block Se deve bloquear ou não
 * @param string $ip           Endereço IP do visitante
 * @param string $country_code Código do país (US, BR, etc)
 * @return bool
 */
add_filter( 'geo_blocker_should_block', function( $should_block, $ip, $country_code ) {
    // Nunca bloquear IPs que começam com 192.168
    if ( strpos( $ip, '192.168' ) === 0 ) {
        return false;
    }

    // Sempre bloquear país XX
    if ( $country_code === 'XX' ) {
        return true;
    }

    return $should_block;
}, 10, 3 );
```

#### `geo_blocker_message`

Customiza a mensagem de bloqueio.

```php
/**
 * @param string $message      Mensagem padrão
 * @param string $ip           IP do visitante
 * @param string $country_code Código do país
 * @return string
 */
add_filter( 'geo_blocker_message', function( $message, $ip, $country_code ) {
    return sprintf(
        'Acesso negado do país %s. Entre em contato: suporte@exemplo.com',
        $country_code
    );
}, 10, 3 );
```

*(Consulte README.md completo para mais hooks)*

## ⚡ Performance

O plugin utiliza estratégia de cache multi-camadas para máxima performance.

Consulte [README.md completo](./README.md) para detalhes sobre otimização.

## 🔒 Segurança

Todos os aspectos de segurança foram implementados:
- Validação de inputs
- Prepared statements
- Nonce verification
- Rate limiting
- XSS/CSRF protection

## 🧪 Testes

```bash
# Executar todos os testes
phpunit

# Teste específico
phpunit tests/test-ip-manager.php
```

Consulte [TESTING.md](./geo-ip-blocker/TESTING.md) para checklist completo.

## 📄 Licença

GPL v2 ou posterior

## 📞 Suporte

- **Issues**: [GitHub Issues](https://github.com/JRG-code/Geo-and-IP-block/issues)
- **Email**: support@exemplo.com

---

**Desenvolvido por [JRG Code](https://github.com/JRG-code)**
