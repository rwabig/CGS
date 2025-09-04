-- Roles
INSERT INTO roles (name, slug) VALUES
  ('Administrator','admin'),
  ('Student','student'),
  ('Signatory','signatory'),
  ('Academic Admin','academic_admin');

-- Departments / Units mapped from ARU forms (UG & PG)
INSERT INTO departments (name, slug) VALUES
  ('Head of Department','hod'),
  ('Librarian – ARU','library'),
  ('Land Survey Store','ls_store'),
  ('Games Coach','games'),
  ('Halls of Residence – Warden','halls'),
  ('Accounts Section','accounts'),
  ('Director of Student Services','dss'),
  ('Academic Administration','academic_admin'),
  ('Dean of Students','dean_students'),
  ('Director of Library','director_library'),
  ('Supervisor','supervisor'),
  ('Dean of School/Director of Institute','dean_school'),
  ('Director of Postgraduate Studies','dpgs'),
  ('Director of CICT','cict'),
  ('Examinations Office','exams');
