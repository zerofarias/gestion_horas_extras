-- Curso semilla: Excel básico para el día a día (ejecutar después de migration_learning.sql)
-- Asigna a empresa Ecofarma; crea área Administración si no existe.

SET @company_id = (SELECT id FROM companies WHERE name = 'Ecofarma' LIMIT 1);

INSERT INTO areas (company_id, name, is_active)
SELECT @company_id, 'Administración', 1 FROM DUAL
WHERE @company_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM areas WHERE company_id = @company_id AND name = 'Administración'
  );

SET @area_id = (
  SELECT id FROM areas WHERE company_id = @company_id AND name = 'Administración' LIMIT 1
);

INSERT INTO courses (
  company_id, area_id, title, slug, description,
  stars_on_complete, passing_score, estimated_minutes,
  require_quiz, max_quiz_attempts, is_published, sort_order
)
SELECT
  @company_id,
  @area_id,
  'Excel básico para el día a día',
  'excel-basico-dia-a-dia',
  'Aprendé a usar Excel en el trabajo: celdas, fórmulas, tablas y gráficos simples.',
  5, 70, 58, 1, 3, 1, 1
FROM DUAL
WHERE @company_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM courses WHERE company_id = @company_id AND slug = 'excel-basico-dia-a-dia'
  );

SET @course_id = (
  SELECT id FROM courses WHERE company_id = @company_id AND slug = 'excel-basico-dia-a-dia' LIMIT 1
);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 1, 'Interfaz y celdas', 'text',
  'Excel se organiza en filas (números) y columnas (letras). Cada intersección es una celda. La barra de fórmulas muestra el contenido de la celda activa. Practica escribir texto y números en A1, B1 y C1.',
  8, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 1);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 2, 'Formatos y datos', 'text',
  'Podés dar formato a números (moneda, porcentaje), fechas y texto. Usá Inicio > Formato de número. Alineá encabezados en negrita para tablas legibles.',
  10, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 2);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 3, 'Fórmulas básicas (SUMA, PROMEDIO)', 'text',
  'Las fórmulas empiezan con =. SUMA(A1:A10) suma un rango; PROMEDIO(A1:A10) calcula el promedio. Siempre verificá referencias de celdas.',
  12, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 3);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 4, 'Referencias y autocompletar', 'text',
  'Arrastrá el controlador de relleno para copiar fórmulas. Las referencias relativas cambian al copiar; las absolutas ($A$1) no.',
  10, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 4);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 5, 'Tablas y filtros simples', 'text',
  'Convertí un rango en Tabla (Ctrl+T) para filtros automáticos. Usá la flecha del encabezado para filtrar por valor.',
  10, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 5);

INSERT INTO course_lessons (course_id, position, title, content_type, content_body, duration_minutes, is_required)
SELECT @course_id, 6, 'Gráficos introductorios', 'text',
  'Seleccioná datos y elegí Insertar > Gráfico recomendado. Columnas para comparar; líneas para tendencias en el tiempo.',
  8, 1 FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_lessons WHERE course_id = @course_id AND position = 6);

-- Preguntas del cuestionario (10)
INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 1, '¿Qué hace la función SUMA?', 'SUMA agrega los valores numéricos de un rango de celdas.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 1);

SET @q1 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 1 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct)
SELECT @q1, 'Suma los valores de un rango', 1 FROM DUAL WHERE @q1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q1 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q1, 'Calcula el promedio', 0 FROM DUAL WHERE @q1 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q1, 'Cuenta celdas vacías', 0 FROM DUAL WHERE @q1 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q1, 'Ordena de mayor a menor', 0 FROM DUAL WHERE @q1 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 2, '¿Cómo se referencia la celda en columna A fila 1?', 'La notación es letra de columna + número de fila: A1.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 2);
SET @q2 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 2 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q2, 'A1', 1 FROM DUAL WHERE @q2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q2 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q2, '1A', 0 FROM DUAL WHERE @q2 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q2, 'A-1', 0 FROM DUAL WHERE @q2 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q2, 'FilaA1', 0 FROM DUAL WHERE @q2 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 3, '¿Con qué símbolo empiezan las fórmulas en Excel?', 'Toda fórmula debe comenzar con el signo igual (=).'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 3);
SET @q3 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 3 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q3, '=', 1 FROM DUAL WHERE @q3 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q3 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q3, '+', 0 FROM DUAL WHERE @q3 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q3, '#', 0 FROM DUAL WHERE @q3 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q3, '@', 0 FROM DUAL WHERE @q3 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 4, '¿Qué función calcula el promedio?', 'PROMEDIO devuelve la media aritmética del rango.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 4);
SET @q4 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 4 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q4, 'PROMEDIO', 1 FROM DUAL WHERE @q4 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q4 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q4, 'SUMA', 0 FROM DUAL WHERE @q4 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q4, 'MAX', 0 FROM DUAL WHERE @q4 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q4, 'CONTAR', 0 FROM DUAL WHERE @q4 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 5, '¿Para qué sirve convertir datos en Tabla (Ctrl+T)?', 'Las tablas facilitan filtros, formato y referencias estructuradas.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 5);
SET @q5 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 5 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Filtros y formato automático', 1 FROM DUAL WHERE @q5 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q5 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Bloquear el archivo', 0 FROM DUAL WHERE @q5 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Eliminar duplicados siempre', 0 FROM DUAL WHERE @q5 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Imprimir en PDF', 0 FROM DUAL WHERE @q5 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 6, '¿Qué atajo crea una tabla a partir del rango seleccionado?', 'Ctrl+T (o Ctrl+L en algunas versiones) crea una tabla.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 6);
SET @q6 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 6 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q6, 'Ctrl+T', 1 FROM DUAL WHERE @q6 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q6 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q6, 'Ctrl+C', 0 FROM DUAL WHERE @q6 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q6, 'Ctrl+P', 0 FROM DUAL WHERE @q6 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q6, 'Ctrl+Z', 0 FROM DUAL WHERE @q6 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 7, '¿Qué tipo de gráfico conviene para comparar categorías?', 'Los gráficos de columnas o barras comparan valores entre categorías.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 7);
SET @q7 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 7 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q7, 'Columnas', 1 FROM DUAL WHERE @q7 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q7 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q7, 'Circular para series largas', 0 FROM DUAL WHERE @q7 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q7, 'Solo mapa', 0 FROM DUAL WHERE @q7 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q7, 'Ninguno', 0 FROM DUAL WHERE @q7 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 8, 'La referencia $A$1 es…', 'El símbolo $ fija fila y/o columna al copiar fórmulas.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 8);
SET @q8 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 8 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Absoluta', 1 FROM DUAL WHERE @q8 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q8 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Relativa siempre', 0 FROM DUAL WHERE @q8 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Un error', 0 FROM DUAL WHERE @q8 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Solo texto', 0 FROM DUAL WHERE @q8 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 9, '¿Dónde ves el contenido de la celda activa al editar?', 'La barra de fórmulas muestra y permite editar el contenido.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 9);
SET @q9 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 9 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Barra de fórmulas', 1 FROM DUAL WHERE @q9 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q9 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Panel de impresión', 0 FROM DUAL WHERE @q9 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Comentarios', 0 FROM DUAL WHERE @q9 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Vista previa', 0 FROM DUAL WHERE @q9 IS NOT NULL;

INSERT INTO course_quiz_questions (course_id, position, question_text, explanation)
SELECT @course_id, 10, 'Para aprobar este curso necesitás al menos…', 'El curso está configurado con 70% de aciertos en el quiz.'
FROM DUAL WHERE @course_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_questions WHERE course_id = @course_id AND position = 10);
SET @q10 = (SELECT id FROM course_quiz_questions WHERE course_id = @course_id AND position = 10 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q10, '70% de respuestas correctas', 1 FROM DUAL WHERE @q10 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM course_quiz_options WHERE question_id = @q10 LIMIT 1);
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q10, '100% obligatorio', 0 FROM DUAL WHERE @q10 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q10, '50%', 0 FROM DUAL WHERE @q10 IS NOT NULL;
INSERT INTO course_quiz_options (question_id, option_text, is_correct) SELECT @q10, 'No hay quiz', 0 FROM DUAL WHERE @q10 IS NOT NULL;

-- Asignaciones: empresa + área Administración
INSERT INTO course_assignments (course_id, target_type, target_id)
SELECT @course_id, 'company', @company_id FROM DUAL
WHERE @course_id IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM course_assignments WHERE course_id = @course_id AND target_type = 'company' AND target_id = @company_id
);

INSERT INTO course_assignments (course_id, target_type, target_id)
SELECT @course_id, 'area', @area_id FROM DUAL
WHERE @course_id IS NOT NULL AND @area_id IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM course_assignments WHERE course_id = @course_id AND target_type = 'area' AND target_id = @area_id
);

-- Premio ejemplo: 100 estrellas
INSERT INTO rewards (company_id, title, description, stars_required, is_active)
SELECT @company_id, 'Gift card $5000', 'Canjeá 100 estrellas por una gift card (sujeto a aprobación de RRHH).', 100, 1
FROM DUAL
WHERE @company_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rewards WHERE company_id = @company_id AND title = 'Gift card $5000');
