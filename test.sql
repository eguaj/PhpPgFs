/*

pgfs:///home/john.doe/foo.txt

    fs_files
==================
 id | type | name
----+------+------
  1 |  'd' | 'home'
  2 |  'd' | 'john.doe'
  3 |  'f' | 'foo.txt'
  4 |  'f' | 'bar.txt'
------------------

  fs_dirs
============
 dir | file
-----+------
   0 | 1
   1 | 2
   2 | 3
   2 | 4
------------

  fs_data
===========
 id | data
----+------
  3 | 'Hello world.'
  4 | 'This is not a test.'
-----------

*/

CREATE TABLE fs_files (id int NOT NULL, type text NOT NULL, name text NOT NULL);
CREATE TABLE fs_dirs (dir int NOT NULL, file int NOT NULL);
CREATE TABLE fs_data (id int NOT NULL, data bytea);

INSERT INTO fs_files VALUES
  (1, 'd', 'home'),
  (2, 'd', 'john.doe'),
  (3, 'f', 'foo.txt'),
  (4, 'f', 'bar.txt')
  ;
INSERT INTO fs_dirs VALUES
  (0, 1),
  (1, 2),
  (2, 3),
  (2,4)
  ;
INSERT INTO fs_data VALUES
  (3, 'Hello world.'),
  (4, 'This is not a test.')
  ;
