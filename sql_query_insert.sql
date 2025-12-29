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
'Author Name',
1,
299.00,
'14npPK8xtMEhbl76PF8_a7Com_QTe0zGm',
'uploads/covers/introduction-to-computer-ii.jpg'
);

-- Sample Ebooks only
INSERT INTO ebooks
(title, description, author, category_id, price, file_path, cover_image)
VALUES
(
    'Python Programming',
    'Learn Python programming from scratch with practical examples and exercises.',
    'Author Name',
    1,
    399.00,
    '1PYTnABcdeFGHijklmnopQRStuvWXyz',
    'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1675921665i/107526170.jpg'
),
(
    'PHP for Beginners',
    'A complete guide to PHP for web development and server-side programming.',
    'Author Name',
    1,
    349.00,
    '2PHPnABcdeFGHijklmnopQRStuvWXyz',
    'https://tse1.mm.bing.net/th/id/OIP.szI6UyfsRYKBvHOe4A_IQwHaJQ?rs=1&pid=ImgDetMain&o=7&rm=3'
),
(
    'Java Programming Fundamentals',
    'Comprehensive coverage of Java basics, object-oriented programming, and more.',
    'Author Name',
    1,
    499.00,
    '3JAVAabcDEFGhijklmnopQRStuvWXyz',
    'https://th.bing.com/th/id/R.52f1aa4e2ce1d7d5a0cf7d762be68a6c?rik=CVEV%2fT7mjDJZCQ&riu=http%3a%2f%2f2.bp.blogspot.com%2f-zSyvKYufQNA%2fU4ppL0ChaEI%2fAAAAAAAAAQM%2fxW6jDrOphzQ%2fs1600%2fCover.jpg&ehk=8XlTo9pXFcS096bscWs1qNskkotqW1TDLk3lDiPyiM0%3d&risl=&pid=ImgRaw&r=0'
),
(
    'C++ Programming Basics',
    'An introduction to C++ programming language and fundamental programming concepts.',
    'Author Name',
    1,
    459.00,
    '4CPPabcDEFGhijklmnopQRStuvWXyz',
    'https://tse1.mm.bing.net/th/id/OIP.5j7C8pf414eOxsYpNletFQHaKl?rs=1&pid=ImgDetMain&o=7&rm=3'
);






-- Insert Additional Categories (Soon)
('Programming Language'),
('Web Development'),
('Mobile App Development'),
('Database Systems'),
('Backend Development'),
('Frontend Development')