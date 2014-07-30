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

### Example Expressions

Find php files
```SQL
extension = 'php'
```

Find files containing lorem ipsum
```SQL
content contains "lorem ipsum"
```
