lsql
====

Use SQL-style syntax to find the files you're looking for from the command line


### Usage
```Shell
lsql.php [ directoryA, ... ] "expression"
```

### Columns
- name (string)
- path (string)
- extension (string)
- content (string)

### Examples

Find php files
```Shell
lsql.php "extension = 'php'"
```

Find files containing lorem ipsum
```Shell
lsql.php "content contains 'lorem ipsum'"
```
