# Manual de uso — CMMS Industrial

Documento orientado a **utilizadores finais** (oficina, lubrificação, gestão, administradores de sistema leigos em código). Para especificação técnica da API, perfis ao detalhe e deploy, veja o [`readme.md`](../readme.md).

---

## 1. O que é o sistema

O CMMS é um **painel operacional** para acompanhar **máquinas**, **ordens de serviço (OS)**, **manutenção preventiva**, **lubrificação**, **óleo solúvel (emulsão)** e **peças** numa planta industrial de pequeno / médio porte. O objetivo é substituir planilhas dispersas por um registo único, simples e rápido no dia a dia.

**Idioma da interface:** português do Brasil (pt-BR).

---

## 2. Como aceder

1. Abra o endereço do CMMS no navegador (em produção costuma ser **HTTPS**, por exemplo `https://seu-dominio/`).
2. Na página de **login**, introduza o **e-mail** e a **senha** fornecidos pelo administrador.
3. Após entrar, o sistema abre o **dashboard** adequado ao seu **perfil** (função).

**Trocar a própria senha:** se o administrador tiver permitido no seu cadastro, use a opção de menu correspondente (quando visível). Se estiver desativado, peça alteração a um **ADMIN**.

**API Base:** em instalações corretas com Nginx, o front-end fala com a API na mesma origem (ex.: `/api/v1`). Não é necessário configurar nada no browser.

---

## 3. Perfis (funções)

Cada utilizador tem um **perfil** que define o que pode ver e alterar. Resumo prático:

| Perfil | Papel típico |
|--------|----------------|
| **ADMIN** | Acesso total: cadastros (ativos, peças, setores, utilizadores, checklists, lubrificantes, etc.), OS, relatórios, **backup**, **agendador** de tarefas no servidor. |
| **TECNICO** | Opera a manutenção: OS (status via **apontamentos**), preventivas, lubrificação, emulsão, consulta a ativos e peças, relatórios. |
| **LUBRIFICADOR** | Foco em **lubrificação** e **óleo solúvel**; o **menu** mostra sobretudo o **Dashboard Lubrificação** e o grupo **Lubrificação** (**Tarefas**, **Óleo solúvel**). Na API o mesmo perfil pode ter permissões mais amplas (por exemplo OS); o acesso a **Ordens de serviço** no browser depende da política da empresa e do que o menu exibir na sua instalação. |
| **DIRETORIA** | Visão de leitura na maior parte das áreas; pode **abrir OS** e **anexar/remover ficheiros** nas OS. Adequado a acompanhamento sem operar cadastros. |
| **LIDER** | Acompanha OS; em estado **Aguardando aprovação** pode trabalhar o checklist de **finalização**; pode alterar o **perfil de usinagem** (leve/pesado) nos ativos. **Finalizar** ou **cancelar** OS segue as mesmas regras que para **ADMIN** (com checklist de finalização concluído quando aplicável). |
| **USUARIO** | Operador ou solicitante: **abre** OS, **anexa** fotos/PDF, acompanha o andamento; **não** muda status nem cadastros. Vê sobretudo o dashboard de utilizador e **Ordens de serviço**. |

Se uma página devolver **“acesso negado”** ou **erro 403**, o seu perfil não tem permissão para essa função.

---

## 4. Navegação (menu lateral)

O menu agrupa áreas de forma fixa:

1. **Dashboard** — indicadores resumidos (OS abertas, máquinas paradas, preventivas, lubrificações do dia, peças abaixo do mínimo onde aplicável, etc.). Quem é **ADMIN** vê um submenu com vários dashboards (Admin, Técnico, Lubrificação, …).
2. **Ordem de Serviço** — **Ordens de serviço**, **Preventivas**, **Consolidação** (quando o perfil permitir).
3. **Lubrificação** — **Tarefas** (pontos a executar) e **Óleo solúvel** (emulsão), para perfis com acesso.
4. **Cadastros** — **Ativos**, **Setores**, **Categorias**, **Checklists**, **Óleos** (lubrificantes), **Itens** (peças), **Utilizadores** — na maior parte dos casos só **ADMIN** grava aqui.
5. **Manutenção** (só **ADMIN**) — **Backup** e **Agendador** (tarefas automáticas no servidor: backups, preventivas agendadas, atualização de bibliotecas do site, etc.).
6. **Relatórios** — consultas e exportações (conforme perfil).

Os nomes exatos no ecrã podem ser ligeiramente diferentes (ex.: “Itens” para o catálogo de peças).

---

## 5. Ordens de serviço (OS)

### 5.1 Conceitos

- Cada OS está ligada a um **ativo** (máquina), tem um **código**, **tipo** de manutenção, **prioridade** e **estado**.
- Estados usuais incluem: **Aberta**, **Agendada**, **Em execução**, **Aguardando peça**, **Aguardando terceiro**, **Aguardando aprovação**, **Finalizada**, **Cancelada**.
- O **histórico** da OS (quem fez o quê e quando) regista-se em **apontamentos**.
- **Anexos:** fotos ou PDF guardados na OS (útil para evidências).
- **Solicitações de peças:** pedidos de material associados à OS; podem influenciar o **estoque** quando a peça está marcada para controlo de stock e a OS é finalizada (regra tratada pelo sistema).

### 5.2 Abrir uma OS

1. Em **Ordens de serviço**, use **Nova OS** (ou equivalente).
2. Escolha o **ativo**, descreva o problema e preencha os campos pedidos.
3. Opcionalmente marque o ativo como **parado**, se a situação o justificar.

### 5.3 Checklists (LOTO e finalização)

- **LOTO** (bloqueio e etiquetagem): em geral é **obrigatório concluir** este checklist para **sair de “Aberta”** para estados operacionais (exceto **Agendada** e **Cancelada**, conforme regras do sistema).
- **Finalização:** antes de **Finalizar** a OS, o checklist de **finalização** deve estar **concluído** (quem pode editar este checklist em “Aguardando aprovação” inclui **LIDER** e **ADMIN**).
- A lista de tarefas do checklist pode ser copiada a partir de **padrões** definidos nos cadastros.

### 5.4 Registar apontamentos (mudar estado)

1. Abra o **detalhe** da OS.
2. Use **Novo apontamento** (ou similar): texto da intervenção, **período** (início/fim) quando aplicável, e o **novo estado** desejado.
3. Perfis sem permissão não conseguem colocar a OS em estados restritos (ex.: **Finalizada** / **Cancelada** só para quem a política permitir).

### 5.5 Anexos

No detalhe da OS, envie **imagens** ou **PDF** dentro do limite de tamanho definido no servidor. Quem abriu a OS costuma poder remover anexos que tenha carregado, conforme política.

### 5.6 Preventivas geradas pelo sistema

As OS de **preventiva** podem ser criadas a partir dos **planos** de manutenção e aparecem como tipo preventivo; o fluxo de checklists e apontamentos é o mesmo em grande parte dos casos.

---

## 6. Preventivas (planos)

- Em **Preventivas**, o **ADMIN** (ou quem tenha acesso) gere **planos** por ativo: periodicidade, ativo/inativo e datas de referência.
- A **execução** de um plano vencido gera OS em estado adequado (por exemplo **Agendada**, com data de agendamento).
- O cartão de preventivas pode permitir escolher o **solicitante** das OS geradas automaticamente (utilizador de referência).

---

## 7. Lubrificação

- **Óleos** (cadastro de lubrificantes): sobretudo **ADMIN**.
- **Tarefas:** execução dos **pontos de lubrificação** por máquina, com registo de leituras (ex.: litros aplicados) quando configurado.
- O sistema pode mostrar **lubrificações do dia** ou atrasadas nos dashboards.

---

## 8. Óleo solúvel (emulsão)

- Área dedicada a máquinas com **controlo de emulsão** ativo no cadastro do ativo.
- Registe **inspeções** (concentração / pH, etc.); o sistema pode sugerir ajustes e alterar o estado operacional do ativo (**parado** quando exige correção, **operando** quando ok), conforme regras configuradas.

---

## 9. Peças (itens / almoxarifado)

- Catálogo com **código**, **descrição**, **quantidades** e opção de **controlar estoque** (alertas de mínimo, baixa na finalização de OS quando aplicável).
- **ADMIN** trata importações em massa (CSV) e alterações de cadastro quando existirem no menu.

---

## 10. Relatórios e consolidação

- **Relatórios:** indicadores e listagens exportáveis (conforme perfil).
- **Consolidação:** visão administrativa para conferência de **custos** e dados de OS **finalizadas** (uso típico de **ADMIN** / **Diretoria**, conforme menu). A OS pode estar **finalizada** na operação e **consolidada** mais tarde só para métricas.

---

## 11. Manutenção do sistema (ADMIN)

- **Backup:** gerar ou restaurar cópias da base e, quando aplicável, do sistema (seguir instruções no ecrã e a documentação de deploy).
- **Agendador:** configurar intervalos e próximas execuções de tarefas de servidor (backup automático, geração de preventivas, atualização de ficheiros JS/CSS locais, etc.). Pode existir um ícone de **ajuda** no ecrã com texto explicativo.

Estas áreas são **sensíveis**; devem ser usadas apenas por pessoal autorizado.

---

## 12. Boas práticas

- Prefira **HTTPS** e não partilhe credenciais.
- Registe **apontamentos** com texto claro e **horários** fiéis para melhor rastreio do tempo de intervenção.
- Mantenha **cadastros** (ativos, peças, planos) atualizados para relatórios e preventivas corretos.
- Em dúvida sobre **perfil** ou **estado da OS**, fale com o **ADMIN** ou supervisor.

---

## 13. Onde obter ajuda técnica

| Necessidade | Documento |
|-------------|-----------|
| Instalação de servidores, Nginx, PostgreSQL | [`deploy/README.md`](../deploy/README.md), [`docs/DEPENDENCIES.md`](DEPENDENCIES.md) |
| Reset de base ou limpeza de histórico para produção | [`database/README.md`](../database/README.md), [`scripts/README.md`](../scripts/README.md) |
| Índice geral do projeto | [`docs/CONTINUIDADE.md`](CONTINUIDADE.md) |
| Contrato da API e detalhe de perfis | [`readme.md`](../readme.md) |

---

*Manual de uso — CMMS. Revise este texto quando mudarem menus ou regras de negócio visíveis ao utilizador.*
