��          t      �                  ,     M  *   c  %   �  2   �  2   �  ,     :   G  3   �  P  �  d     �   l  .   !  Y   P  :   �  I   �  R   /  K   �  r   �  ?   A                       
             	                        i18n_MySQL_ConnectionError i18n_MySQL_ConnectionNotExisting i18n_MySQL_QueryError i18n_MySQL_QueryInvalidReturnTypeSpecified i18n_MySQL_QueryNoReturnTypeSpecified i18n_MySQL_QueryResultExpected1ColumnResultedOther i18n_MySQL_QueryResultExpected1OrMoreRows0Resulted i18n_MySQL_QueryResultExpected1ResultedOther i18n_MySQL_QueryResultExpected1RowManyColumnsResultedOther i18n_MySQL_QueryResultExpected2ColumnsResultedOther Project-Id-Version: CommonLib
POT-Creation-Date: 2015-01-20 16:09+0200
PO-Revision-Date: 2015-01-20 17:04+0200
Last-Translator: Daniel Popiniuc <danielpopiniuc@gmail.com>
Language-Team: 
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Language: en_US
Plural-Forms: nplurals=2; plural=(n != 1);
 Connection error (no.: %d, message from server: %s, host = %s, port: %s, username: %s, database: %s) There is no MySQL connection at this moment, please check your code and make sure the connection is properly instantiated and also check if target MySQL server is working properly! Query error (no.: %d, message from server: %s) The provided return type (%s) is not among the values the function %s knows how to handle There is no return type specified when calling function %s The MySQL query returned %d columns although a single one was expected... The MySQL query did not returned any records although at least one was expected... The MySQL query returned %d records although 1 single value was expected... The MySQL query returned %d records and %d columns, although a single record with multiple columns was expected... The MySQL query returned %d columns although 2 were expected... 