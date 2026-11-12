SET NAMES utf8mb4;

DROP DATABASE IF EXISTS forg3d;
CREATE DATABASE forg3d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forg3d;

CREATE TABLE configuracoes (
  id                TINYINT PRIMARY KEY,
  nome_empresa      VARCHAR(80)   NOT NULL DEFAULT 'FORG3D',
  tarifa_kwh        DECIMAL(6,3)  NOT NULL DEFAULT 0.950,
  margem_padrao     DECIMAL(5,2)  NOT NULL DEFAULT 60.00,
  taxa_falha_padrao DECIMAL(5,2)  NOT NULL DEFAULT 10.00
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  email     VARCHAR(120) NOT NULL UNIQUE,
  senha     VARCHAR(255) NOT NULL,
  nivel     ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  ativo     TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tokens_senha (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token      CHAR(64) NOT NULL,
  expira_em  DATETIME NOT NULL,
  usado      TINYINT(1) NOT NULL DEFAULT 0,
  criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE clientes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(120) NOT NULL,
  tipo        ENUM('PF','PJ') NOT NULL DEFAULT 'PF',
  documento   VARCHAR(20),
  email       VARCHAR(120),
  telefone    VARCHAR(20),
  cidade      VARCHAR(80),
  observacoes TEXT,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE materiais (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  tipo             VARCHAR(30)  NOT NULL,
  marca            VARCHAR(60)  NOT NULL,
  cor              VARCHAR(40)  NOT NULL,
  cor_hex          CHAR(7)      NOT NULL DEFAULT '#888888',
  diametro         DECIMAL(4,2) NOT NULL DEFAULT 1.75,
  preco_kg         DECIMAL(8,2) NOT NULL,
  estoque_g        DECIMAL(10,2) NOT NULL DEFAULT 0,
  estoque_minimo_g DECIMAL(10,2) NOT NULL DEFAULT 300,
  criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE impressoras (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(60)  NOT NULL,
  marca        VARCHAR(60)  NOT NULL,
  modelo       VARCHAR(60)  NOT NULL,
  tecnologia   ENUM('FDM','SLA') NOT NULL DEFAULT 'FDM',
  potencia_w   INT          NOT NULL,
  valor_compra DECIMAL(10,2) NOT NULL,
  vida_util_h  INT          NOT NULL DEFAULT 8000,
  horas_uso    DECIMAL(10,1) NOT NULL DEFAULT 0,
  status       ENUM('disponivel','em_uso','manutencao') NOT NULL DEFAULT 'disponivel',
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE modelos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(120) NOT NULL,
  categoria   VARCHAR(60),
  descricao   TEXT,
  peso_g      DECIMAL(8,2) NOT NULL,
  tempo_h     DECIMAL(6,2) NOT NULL,
  material_id INT NULL,
  imagem      VARCHAR(150),
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (material_id) REFERENCES materiais(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE orcamentos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id    INT NOT NULL,
  usuario_id    INT NOT NULL,
  status        ENUM('pendente','aprovado','em_producao','concluido','cancelado') NOT NULL DEFAULT 'pendente',
  margem        DECIMAL(5,2) NOT NULL,
  taxa_falha    DECIMAL(5,2) NOT NULL,
  tarifa_kwh    DECIMAL(6,3) NOT NULL,
  custo_total   DECIMAL(10,2) NOT NULL DEFAULT 0,
  preco_final   DECIMAL(10,2) NOT NULL DEFAULT 0,
  prazo_entrega DATE NULL,
  observacoes   TEXT,
  sugestao_ia   TEXT,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  concluido_em  DATETIME NULL,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE orcamento_itens (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  orcamento_id   INT NOT NULL,
  modelo_id      INT NULL,
  descricao      VARCHAR(150) NOT NULL,
  material_id    INT NOT NULL,
  impressora_id  INT NOT NULL,
  quantidade     INT NOT NULL DEFAULT 1,
  peso_g         DECIMAL(8,2) NOT NULL,
  tempo_h        DECIMAL(6,2) NOT NULL,
  custo_material DECIMAL(10,2) NOT NULL,
  custo_energia  DECIMAL(10,2) NOT NULL,
  custo_maquina  DECIMAL(10,2) NOT NULL,
  custo_unit     DECIMAL(10,2) NOT NULL,
  preco_unit     DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (orcamento_id)  REFERENCES orcamentos(id)  ON DELETE CASCADE,
  FOREIGN KEY (modelo_id)     REFERENCES modelos(id)     ON DELETE SET NULL,
  FOREIGN KEY (material_id)   REFERENCES materiais(id)   ON DELETE RESTRICT,
  FOREIGN KEY (impressora_id) REFERENCES impressoras(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE movimentacoes_estoque (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  material_id  INT NOT NULL,
  tipo         ENUM('entrada','saida','ajuste') NOT NULL,
  quantidade_g DECIMAL(10,2) NOT NULL,
  motivo       VARCHAR(150),
  orcamento_id INT NULL,
  usuario_id   INT NULL,
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (material_id)  REFERENCES materiais(id)  ON DELETE RESTRICT,
  FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE SET NULL,
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ia_consultas (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  pergunta   TEXT NOT NULL,
  resposta   TEXT NOT NULL,
  modo       ENUM('claude','demo') NOT NULL,
  criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO configuracoes VALUES (1, 'FORG3D', 0.95, 60.0, 10.0);

INSERT INTO usuarios (nome, email, senha, nivel, criado_em) VALUES
  ('Diego Date', 'admin@forg3d.com', '$2y$10$Hri8/Kz523S5jbrnC0DV6.PsrJl81llizkhoHrSowZRF4zIfTf5cW', 'admin', DATE_SUB(NOW(), INTERVAL 170 DAY)),
  ('Luan Bariani', 'operador@forg3d.com', '$2y$10$G94PpfVINwqc2VqGiJYl3eTz9SftiI9GIJldEZwZLdjby0pZR05qW', 'operador', DATE_SUB(NOW(), INTERVAL 170 DAY));

INSERT INTO clientes (nome, tipo, documento, email, telefone, cidade, observacoes, criado_em) VALUES
  ('Ana Paula Ribeiro', 'PF', '111.222.333-44', 'ana.ribeiro@email.com', '(18) 99712-3344', 'Presidente Prudente', 'Prefere peças brancas e acabamento lixado.', DATE_SUB(NOW(), INTERVAL 165 DAY)),
  ('Oficina Maker Prudente', 'PJ', '11.222.333/0001-44', 'contato@makerprudente.com.br', '(18) 3221-4567', 'Presidente Prudente', 'Cliente recorrente — peças técnicas.', DATE_SUB(NOW(), INTERVAL 156 DAY)),
  ('Rafael Monteiro', 'PF', '222.333.444-55', 'rafa.monteiro@email.com', '(18) 99654-1122', 'Álvares Machado', '', DATE_SUB(NOW(), INTERVAL 147 DAY)),
  ('Clínica OdontoSorriso', 'PJ', '22.333.444/0001-55', 'compras@odontosorriso.com.br', '(18) 3903-7788', 'Pirapozinho', 'Brindes com a logo da clínica.', DATE_SUB(NOW(), INTERVAL 138 DAY)),
  ('Juliana Costa', 'PF', '333.444.555-66', 'ju.costa@email.com', '(18) 99801-5566', 'Regente Feijó', '', DATE_SUB(NOW(), INTERVAL 129 DAY)),
  ('Escola Técnica Futuro', 'PJ', '33.444.555/0001-66', 'coordenacao@etfuturo.edu.br', '(18) 3222-9900', 'Presidente Prudente', 'Kits didáticos para o laboratório maker.', DATE_SUB(NOW(), INTERVAL 120 DAY));

INSERT INTO materiais (tipo, marca, cor, cor_hex, diametro, preco_kg, estoque_g, estoque_minimo_g, criado_em) VALUES
  ('PLA', 'Voolt3D', 'Preto Carvão', '#1c1c1e', 1.75, 89.9, 2350, 500, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('PLA', 'Voolt3D', 'Branco Gelo', '#eef0f2', 1.75, 89.9, 1800, 500, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('PETG', '3D Fila', 'Laranja Forja', '#ff6a1a', 1.75, 109.9, 950, 400, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('ABS', 'GTMax3D', 'Cinza Grafite', '#4a4f55', 1.75, 79.9, 300, 500, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('TPU', '3D Lab', 'Translúcido', '#cfe8ff', 1.75, 159.9, 420, 250, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('ASA', 'Voolt3D', 'Prata Metálico', '#b8bec6', 1.75, 139.9, 150, 300, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('PLA Silk', 'Sunlu', 'Cobre', '#b87333', 1.75, 119.9, 780, 300, DATE_SUB(NOW(), INTERVAL 168 DAY));

INSERT INTO impressoras (nome, marca, modelo, tecnologia, potencia_w, valor_compra, vida_util_h, horas_uso, status, criado_em) VALUES
  ('Bigorna 01', 'Bambu Lab', 'P1S', 'FDM', 350, 6500.0, 8000, 1352.2, 'em_uso', DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('Martelo 02', 'Creality', 'Ender-3 V3', 'FDM', 270, 1800.0, 6000, 2659.4, 'disponivel', DATE_SUB(NOW(), INTERVAL 168 DAY)),
  ('Brasa 03', 'Prusa', 'MK4S', 'FDM', 240, 7200.0, 10000, 860.0, 'manutencao', DATE_SUB(NOW(), INTERVAL 168 DAY));

INSERT INTO modelos (nome, categoria, descricao, peso_g, tempo_h, material_id, imagem, criado_em) VALUES
  ('Suporte de celular articulado', 'Utilidades', 'Suporte de mesa com dobradiça print-in-place, ângulo ajustável.', 48, 2.6, 1, 'modelo-suporte.svg', DATE_SUB(NOW(), INTERVAL 155 DAY)),
  ('Vaso geométrico low-poly', 'Decoração', 'Vaso facetado em modo vaso (parede única), 18 cm de altura.', 120, 5.5, 2, 'modelo-vaso.svg', DATE_SUB(NOW(), INTERVAL 150 DAY)),
  ('Engrenagem reforçada 40 dentes', 'Peças técnicas', 'Engrenagem de dentes retos, módulo 1,5, furo de 8 mm.', 35, 1.8, 3, 'modelo-engrenagem.svg', DATE_SUB(NOW(), INTERVAL 145 DAY)),
  ('Chaveiro personalizado', 'Brindes', 'Chaveiro com nome ou logo em relevo, 5 cm.', 8, 0.4, 1, 'modelo-chaveiro.svg', DATE_SUB(NOW(), INTERVAL 140 DAY)),
  ('Organizador de mesa modular', 'Escritório', 'Módulos encaixáveis para canetas, clipes e celular.', 210, 9.0, 3, 'modelo-organizador.svg', DATE_SUB(NOW(), INTERVAL 135 DAY)),
  ('Capa flexível para controle', 'Proteção', 'Capa em TPU para controle remoto, impressa sem suportes.', 65, 3.2, 5, 'modelo-capa.svg', DATE_SUB(NOW(), INTERVAL 130 DAY));

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (1, 2, 1, 'concluido', 60.0, 10.0, 0.95, 65.0, 104.0, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 158 DAY), INTERVAL 7 DAY)), 'Lote de engrenagens para manutenção de máquinas.', NULL, DATE_SUB(NOW(), INTERVAL 158 DAY), DATE_SUB(NOW(), INTERVAL 150 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (1, 3, 'Engrenagem reforçada 40 dentes', 3, 1, 10, 35, 1.8, 3.85, 0.6, 1.46, 6.5, 10.4);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (2, 4, 1, 'concluido', 60.0, 10.0, 0.95, 52.0, 83.0, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 128 DAY), INTERVAL 7 DAY)), 'Brindes para campanha de saúde bucal.', NULL, DATE_SUB(NOW(), INTERVAL 128 DAY), DATE_SUB(NOW(), INTERVAL 120 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (2, 4, 'Chaveiro com logo OdontoSorriso', 1, 2, 50, 8, 0.4, 0.72, 0.1, 0.12, 1.04, 1.66);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (3, 1, 2, 'concluido', 60.0, 10.0, 0.95, 37.58, 60.14, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 100 DAY), INTERVAL 7 DAY)), '', NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 95 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (3, 2, 'Vaso geométrico low-poly', 2, 1, 2, 120, 5.5, 10.79, 1.83, 4.47, 18.79, 30.07);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (4, 6, 1, 'concluido', 60.0, 10.0, 0.95, 184.92, 295.88, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 78 DAY), INTERVAL 7 DAY)), 'Kit para o laboratório maker.', NULL, DATE_SUB(NOW(), INTERVAL 78 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (4, 5, 'Organizador de mesa modular', 3, 1, 4, 210, 9.0, 23.08, 2.99, 7.31, 36.72, 58.76),
  (4, 1, 'Suporte de celular articulado', 1, 2, 6, 48, 2.6, 4.32, 0.67, 0.78, 6.34, 10.14);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (5, 3, 1, 'concluido', 60.0, 10.0, 0.95, 30.92, 49.48, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 45 DAY), INTERVAL 7 DAY)), '', NULL, DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (5, 6, 'Capa flexível para controle', 5, 1, 2, 65, 3.2, 10.39, 1.06, 2.6, 15.46, 24.74);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (6, 2, 2, 'concluido', 60.0, 10.0, 0.95, 123.4, 197.6, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 20 DAY), INTERVAL 7 DAY)), 'Segundo lote.', NULL, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (6, 3, 'Engrenagem reforçada 40 dentes', 3, 1, 16, 35, 1.8, 3.85, 0.6, 1.46, 6.5, 10.4),
  (6, NULL, 'Bucha espaçadora 12 mm', 7, 2, 20, 6, 0.3, 0.72, 0.08, 0.09, 0.97, 1.56);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (7, 5, 1, 'concluido', 60.0, 10.0, 0.95, 19.02, 30.42, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 4 DAY), INTERVAL 7 DAY)), '', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (7, 1, 'Suporte de celular articulado', 1, 2, 3, 48, 2.6, 4.32, 0.67, 0.78, 6.34, 10.14);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (8, 4, 1, 'concluido', 60.0, 10.0, 0.95, 38.7, 62.1, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 3 DAY), INTERVAL 7 DAY)), 'Reposição de brindes.', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (8, 4, 'Chaveiro com logo OdontoSorriso', 1, 1, 30, 8, 0.4, 0.72, 0.13, 0.33, 1.29, 2.07);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (9, 6, 2, 'em_producao', 60.0, 10.0, 0.95, 73.44, 117.52, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 3 DAY), INTERVAL 7 DAY)), 'Entrega na secretaria da escola.', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL);
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (9, 5, 'Organizador de mesa modular', 3, 1, 2, 210, 9.0, 23.08, 2.99, 7.31, 36.72, 58.76);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (10, 1, 1, 'aprovado', 60.0, 10.0, 0.95, 17.22, 27.54, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 7 DAY)), 'Imprimir com camada 0,12 mm.', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL);
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (10, NULL, 'Miniatura de dragão para pintura à mão', 2, 3, 1, 95, 7.5, 8.54, 1.71, 5.4, 17.22, 27.54);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (11, 3, 1, 'pendente', 60.0, 10.0, 0.95, 10.68, 17.08, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 7 DAY)), 'Cliente vai buscar pessoalmente.', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL);
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (11, NULL, 'Trava de janela (peça de reposição)', 4, 2, 4, 22, 1.2, 1.76, 0.31, 0.36, 2.67, 4.27);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (12, 5, 2, 'cancelado', 60.0, 10.0, 0.95, 15.23, 24.37, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL 7 DAY)), 'Cliente desistiu.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), NULL);
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (12, 2, 'Vaso geométrico low-poly', 2, 2, 1, 120, 5.5, 10.79, 1.41, 1.65, 15.23, 24.37);

INSERT INTO orcamentos (id, cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, custo_total, preco_final, prazo_entrega, observacoes, sugestao_ia, criado_em, concluido_em) VALUES
  (13, 2, 1, 'pendente', 60.0, 10.0, 0.95, 53.58, 85.68, DATE(DATE_ADD(DATE_SUB(NOW(), INTERVAL 0 DAY), INTERVAL 7 DAY)), 'Peça vai ficar exposta ao sol.', 'Material: ASA — resistente a UV e calor, ideal para uso externo. Preenchimento 40% giroide, 4 paredes, camada 0,20 mm. Usar enclosure fechado para evitar warping.', DATE_SUB(NOW(), INTERVAL 0 DAY), NULL);
INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h, custo_material, custo_energia, custo_maquina, custo_unit, preco_unit) VALUES
  (13, NULL, 'Suporte de sensor para uso externo', 6, 1, 6, 40, 2.2, 5.6, 0.73, 1.79, 8.93, 14.28);

INSERT INTO movimentacoes_estoque (material_id, tipo, quantidade_g, motivo, orcamento_id, usuario_id, criado_em) VALUES
  (1, 'entrada', 2422, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (1, 'entrada', 1000, 'Compra de 1 bobina (1 kg)', NULL, 2, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (1, 'saida', -400, 'Baixa automática — orçamento #2', 2, 1, DATE_SUB(NOW(), INTERVAL 120 DAY)),
  (1, 'saida', -288, 'Baixa automática — orçamento #4', 4, 1, DATE_SUB(NOW(), INTERVAL 70 DAY)),
  (1, 'saida', -144, 'Baixa automática — orçamento #7', 7, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (1, 'saida', -240, 'Baixa automática — orçamento #8', 8, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (2, 'entrada', 2040, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (2, 'saida', -240, 'Baixa automática — orçamento #3', 3, 1, DATE_SUB(NOW(), INTERVAL 95 DAY)),
  (3, 'entrada', 1700, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (3, 'entrada', 1000, 'Compra de 1 bobina (1 kg)', NULL, 2, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (3, 'saida', -350, 'Baixa automática — orçamento #1', 1, 1, DATE_SUB(NOW(), INTERVAL 150 DAY)),
  (3, 'saida', -840, 'Baixa automática — orçamento #4', 4, 1, DATE_SUB(NOW(), INTERVAL 70 DAY)),
  (3, 'saida', -560, 'Baixa automática — orçamento #6', 6, 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
  (4, 'entrada', 300, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (5, 'entrada', 550, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (5, 'saida', -130, 'Baixa automática — orçamento #5', 5, 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
  (6, 'entrada', 150, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (7, 'entrada', 900, 'Estoque inicial do cadastro', NULL, 1, DATE_SUB(NOW(), INTERVAL 168 DAY)),
  (7, 'saida', -120, 'Baixa automática — orçamento #6', 6, 1, DATE_SUB(NOW(), INTERVAL 14 DAY));

ALTER TABLE orcamentos AUTO_INCREMENT = 14;

INSERT INTO ia_consultas (usuario_id, pergunta, resposta, modo, criado_em) VALUES
  (1, 'Suporte de sensor para uso externo, vai ficar no sol o dia todo.', '{"material":"ASA","preenchimento":"40% giroide","camada":"0,20 mm","paredes":"4","suportes":"Não","observacoes":"Usar enclosure fechado para evitar warping."}', 'demo', NOW());
