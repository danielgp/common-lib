
Set WshShell   = WScript.CreateObject("WScript.Shell")
PhpDocumentorLauncher = "D:\BTsync\SU.StableLatest\vendor\phpdocumentor\phpdocumentor\bin\phpdoc.bat"

MsgBox "I will assess the PHP projects for compatibility with predefined standards!"

strCurDir      = WshShell.CurrentDirectory
strBaseDir     = Replace(strCurDir, "docs", "")

WshShell.Run """" & PhpDocumentorLauncher & """ -d """ & strBaseDir & "/source/"" -t """ & strCurDir & """ --title danielgp__common-lib --template=""responsive"" ", 0, True

MsgBox "I finished generating XML files with PHP-Code-Sniffer results!"
