=== Geo & IP Blocker for WooCommerce ===
Contributors: JRG Code
Tags: woocommerce, security, geo-blocking, ip-blocking, access-control
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bloqueie acesso por país, região ou IP para proteger sua loja WooCommerce.

== Description ==

Geo & IP Blocker for WooCommerce é um plugin poderoso que permite bloquear ou permitir acesso ao seu site WooCommerce baseado em:

* Localização geográfica (país)
* Região/Estado
* Endereços IP específicos
* Faixas de IP (CIDR)

= Características Principais =

* Bloqueio por país com lista completa de países
* Bloqueio por região/estado
* Bloqueio por IP individual ou faixa (CIDR)
* Sistema de prioridade para regras
* Logs detalhados de tentativas de acesso bloqueadas
* Interface administrativa intuitiva
* Totalmente compatível com WooCommerce
* Suporte a múltiplas regras de bloqueio
* Ações de bloqueio ou permissão

= Requisitos =

* PHP 7.4 ou superior
* WordPress 5.8 ou superior
* WooCommerce 6.0 ou superior

== Installation ==

1. Faça upload da pasta `geo-ip-blocker` para o diretório `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure as regras de bloqueio em 'Geo & IP Blocker' no menu admin
4. Adicione suas regras de bloqueio conforme necessário

== Frequently Asked Questions ==

= O plugin funciona sem WooCommerce? =

Não, este plugin requer WooCommerce instalado e ativo.

= Como funciona o sistema de prioridade? =

As regras são processadas por ordem de prioridade (menor número = maior prioridade). Regras de "permitir" podem sobrescrever bloqueios anteriores.

= Os logs ocupam muito espaço? =

Você pode configurar a limpeza automática de logs antigos nas configurações do plugin.

= Posso bloquear faixas de IP? =

Sim, o plugin suporta notação CIDR para bloquear faixas de IP (ex: 192.168.1.0/24).

== Screenshots ==

1. Dashboard principal
2. Gerenciamento de regras
3. Visualização de logs
4. Página de configurações

== Changelog ==

= 1.0.0 =
* Lançamento inicial
* Bloqueio por país, região e IP
* Sistema de logs
* Interface administrativa

== Upgrade Notice ==

= 1.0.0 =
Primeira versão do plugin.

== Support ==

Para suporte, visite: https://github.com/JRG-code/Geo-and-IP-block/issues
