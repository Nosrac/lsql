lsql
====

Use SQL-style syntax to find the files you're looking for from the command line


Usage
=====
> lsql.php [ directoryA, ... ] "expression"


Columns
=======
- name (string)
- path (string)
- extension (string)
- content (string)

Examples
========

Find php files
> lsql.php "extension = 'php'"

Find files containing lorem ipsum
> lsql.php "content contains 'lorem ipsum'"
