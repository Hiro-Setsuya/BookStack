-- Insert Categories
INSERT INTO categories (name) VALUES
('Introduction to Computing')


-- Insert Ebooks
INSERT INTO ebooks
(title, description, author, category_id, price, file_path, cover_image)
VALUES
(
'Introduction to Computer',
'A beginner-friendly introduction to computers and basic computing concepts.',
'Author Name',
1,
299.00,
'1EorVYCa8w2YQodqPze5btvlhxITCVYGe',
'uploads/covers/introduction-to-computer.jpg'
),
(
'Introduction to Computer II',
'Continuation of Introduction to Computer, covering more advanced topics.',
1,
299.00,
'14npPK8xtMEhbl76PF8_a7Com_QTe0zGm',
'uploads/covers/introduction-to-computer-ii.jpg'
);






-- Insert Additional Categories (Soon)
('Programming Language'),
('Web Development'),
('Mobile App Development'),
('Database Systems'),
('Backend Development'),
('Frontend Development')