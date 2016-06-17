
Set WshShell   = WScript.CreateObject("WScript.Shell")
CheckStandards = Array("PSR1", "PSR2")

MsgBox "I will assess the PHP projects for compatibility with predefined standards!"

strCurDir      = WshShell.CurrentDirectory
strBaseDir     = Replace(strCurDir, "tests", "")

For Each crtStandard In CheckStandards
    WshShell.Run "D:\www\App\PHP\PHP70\php.exe D:\Git\Cmn\StableLatest\vendor\squizlabs\php_codesniffer\scripts\phpcs -p -v --extensions=php -d date.timezone=""Europe/Bucharest"" --encoding=utf-8 --report=xml --standard=" & crtStandard & " " & strBaseDir & " --report-file=" & strCurDir & "\php-code-sniffer\" & crtStandard & ".xml --ignore=*/data/*,*/tests/*,*/tmp/*,*/vendor/*,*/sample/*", 0, True
Next

MsgBox "I finished generating XML files with PHP-Code-Sniffer results!"
