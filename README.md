lsql
====

Use SQL-style syntax to find the files you're looking for from the command line

===========
== USAGE ==
===========
> lsql.php [ directoryA, ... ] "expression"

=============
== COLUMNS ==
=============
name (string)
path (string)
extension (string)
content (string)

==============
== EXAMPLES ==
==============

# Find php files
> lsql.php "extension = 'php'"

# Find files containing lorem ipsum
> lsql.php "content contains 'lorem ipsum'"
