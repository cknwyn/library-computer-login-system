-- ============================================================
-- ORACLE USERS SEED SCRIPT (PL/SQL WRAPPER)
-- Optimized for APEX: Full Column Alignment
-- ============================================================

BEGIN
    -- 1. CLEANUP PREVIOUS TEST USERS (Keeping Admin)
    DELETE FROM users WHERE user_id NOT IN ('admin');

    -- User 1
    INSERT INTO users (user_id, first_name, last_name, name, email, role, college_id, department_id, degree_id, specialization_id, gender, rank, contact_number, designation, ra_expiry_date, batch, cadre, dob, password_hash)
    VALUES ('21-0001-101', 'Marco', 'Pineda', 'Pineda, Marco', 'marco.pineda@auf.edu.ph', 'student', 
        (SELECT id FROM colleges WHERE code = 'CCS' AND ROWNUM = 1),
        (SELECT id FROM departments WHERE name = 'Department of Information Technology' AND ROWNUM = 1),
        (SELECT id FROM degrees WHERE name = 'BS Information Technology' AND ROWNUM = 1),
        (SELECT id FROM specializations WHERE name = 'Network Security' AND ROWNUM = 1),
        'Male', '3rd Year', '0917-111-2222', 'College of Computer Studies', TO_DATE('2028-06-30','YYYY-MM-DD'), '5', 'Undergraduate', TO_DATE('2003-05-14','YYYY-MM-DD'), '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O');

    -- User 2
    INSERT INTO users (user_id, first_name, last_name, name, email, role, college_id, department_id, degree_id, specialization_id, gender, rank, contact_number, designation, ra_expiry_date, batch, cadre, dob, password_hash)
    VALUES ('21-0002-202', 'Stella', 'Umali', 'Umali, Stella', 'stella.umali@auf.edu.ph', 'student', 
        (SELECT id FROM colleges WHERE code = 'CAMP' AND ROWNUM = 1),
        (SELECT id FROM departments WHERE name = 'Department of Medical Technology' AND ROWNUM = 1),
        (SELECT id FROM degrees WHERE name = 'BS Medical Technology' AND ROWNUM = 1),
        (SELECT id FROM specializations WHERE name = 'Clinical Pharmacy' AND ROWNUM = 1),
        'Female', '4th Year', '0918-222-3333', 'College of Allied Medical Professions', TO_DATE('2027-12-15','YYYY-MM-DD'), '2', 'Undergraduate', TO_DATE('2002-11-20','YYYY-MM-DD'), '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O');

    -- User 3 (Staff)
    INSERT INTO users (user_id, first_name, last_name, name, email, role, college_id, department_id, degree_id, gender, rank, contact_number, designation, ra_expiry_date, batch, cadre, dob, password_hash)
    VALUES ('24-0003-001', 'Paolo', 'Guiao', 'Guiao, Paolo', 'paolo.guiao@auf.edu.ph', 'staff', 
        (SELECT id FROM colleges WHERE code = 'CBA' AND ROWNUM = 1),
        (SELECT id FROM departments WHERE name = 'Department of Accounting' AND ROWNUM = 1),
        (SELECT id FROM degrees WHERE name = 'BS Accountancy' AND ROWNUM = 1),
        'Male', 'Faculty', '0919-333-4444', 'Senior Professor', TO_DATE('2035-01-01','YYYY-MM-DD'), '1', 'Postgraduate', TO_DATE('1978-04-12','YYYY-MM-DD'), '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O');

    -- User 4
    INSERT INTO users (user_id, first_name, last_name, name, email, role, college_id, department_id, degree_id, gender, rank, contact_number, designation, ra_expiry_date, batch, cadre, dob, password_hash)
    VALUES ('21-0004-404', 'Mia', 'Lansangan', 'Lansangan, Mia', 'mia.lansangan@auf.edu.ph', 'student', 
        (SELECT id FROM colleges WHERE code = 'CON' AND ROWNUM = 1),
        (SELECT id FROM departments WHERE name = 'Department of Nursing' AND ROWNUM = 1),
        (SELECT id FROM degrees WHERE name = 'BS Nursing' AND ROWNUM = 1),
        'Female', '2nd Year', '0920-444-5555', 'College of Nursing', TO_DATE('2029-05-20','YYYY-MM-DD'), '8', 'Undergraduate', TO_DATE('2004-09-30','YYYY-MM-DD'), '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O');

    -- User 5
    INSERT INTO users (user_id, first_name, last_name, name, email, role, college_id, department_id, degree_id, specialization_id, gender, rank, contact_number, designation, ra_expiry_date, batch, cadre, dob, password_hash)
    VALUES ('21-0005-505', 'Jasper', 'David', 'David, Jasper', 'jasper.david@auf.edu.ph', 'student', 
        (SELECT id FROM colleges WHERE code = 'CEA' AND ROWNUM = 1),
        (SELECT id FROM departments WHERE name = 'Department of Architecture' AND ROWNUM = 1),
        (SELECT id FROM degrees WHERE name = 'BS Architecture' AND ROWNUM = 1),
        (SELECT id FROM specializations WHERE name = 'Structural Engineering' AND ROWNUM = 1),
        'Male', '5th Year', '0921-555-6666', 'College of Engineering and Architecture', TO_DATE('2026-03-10','YYYY-MM-DD'), '1', 'Undergraduate', TO_DATE('2001-02-15','YYYY-MM-DD'), '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O');

    COMMIT;
END;
/
