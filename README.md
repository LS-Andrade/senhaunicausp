# Senha Única USP Plugin

Este é o plugin **Senha Única USP** para o GLPI, que permite a autenticação utilizando o sistema de login da USP.

## Requisitos

- GLPI instalado e configurado. (Testado na versão 12 em 16/11/2026).
- Composer para gerenciar dependências.

## Instalação

1. Faça o download do plugin diretamente do repositório GitHub:
  ```bash
  git clone https://github.com/LS-Andrade/senhaunicausp.git
  ```
2. Mova o diretório do plugin para a pasta de plugins do GLPI.
3. Acesse o painel administrativo do GLPI.
4. Vá até a seção **Configurar > Plug-ins**.
5. Localize o plugin **Senha Única USP**, clique em **Instalar** e, em seguida, em **Ativar**.

## Configuração

1. No painel do GLPI, acesse **Configurar > Plug-ins > Senha Única USP**.
2. Insira as chaves de integração fornecidas pela USP (Client ID e Client Secret).
3. Salve as configurações.

## Dependências

Este plugin utiliza o pacote [`uspdev/senhaunica:2.0`](https://github.com/uspdev/senhaunica). Certifique-se de instalar as dependências com o Composer:

```bash
composer require uspdev/senhaunica:2.0
```

## Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests.