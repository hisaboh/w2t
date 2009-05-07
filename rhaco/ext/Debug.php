<?php
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Debug{
	static function classese(){
		$result = array();
		$std = array("stdClass","Exception","ErrorException","LibXMLError","XSLTProcessor"
					,"XMLWriter","DOMException","DOMStringList","DOMNameList","DOMImplementationList"
					,"DOMImplementationSource","DOMImplementation","DOMNode","DOMNameSpaceNode"
					,"DOMDocumentFragment","DOMDocument","DOMNodeList","DOMNamedNodeMap","DOMCharacterData"
					,"DOMAttr","DOMElement","DOMText","DOMComment","DOMTypeinfo","DOMUserDataHandler"
					,"DOMDomError","DOMErrorHandler","DOMLocator","DOMConfiguration","DOMCdataSection"
					,"DOMDocumentType","DOMNotation","DOMEntity","DOMEntityReference","DOMProcessingInstruction"
					,"DOMStringExtend","DOMXPath","XMLReader","SimpleXMLElement","RecursiveIteratorIterator"
					,"IteratorIterator","FilterIterator","RecursiveFilterIterator","ParentIterator"
					,"LimitIterator","CachingIterator","RecursiveCachingIterator","NoRewindIterator"
					,"AppendIterator","InfiniteIterator","RegexIterator","RecursiveRegexIterator"
					,"EmptyIterator","ArrayObject","ArrayIterator","RecursiveArrayIterator","SplFileInfo"
					,"DirectoryIterator","RecursiveDirectoryIterator","SplFileObject","SplTempFileObject"
					,"SimpleXMLIterator","LogicException","BadFunctionCallException","BadMethodCallException"
					,"DomainException","InvalidArgumentException","LengthException","OutOfRangeException"
					,"RuntimeException","OutOfBoundsException","OverflowException","RangeException"
					,"UnderflowException","UnexpectedValueException","SplObjectStorage","PDOException"
					,"PDO","PDOStatement","PDORow","SoapClient","SoapVar","SoapServer","SoapFault","SoapParam"
					,"SoapHeader","SQLiteDatabase","SQLiteResult","SQLiteUnbuffered","SQLiteException"
					,"__PHP_Incomplete_Class","php_user_filter","Directory","ReflectionException","Reflection"
					,"ReflectionFunctionAbstract","ReflectionFunction","ReflectionParameter","ReflectionMethod"
					,"ReflectionClass","ReflectionObject","ReflectionProperty","ReflectionExtension"
					,"mysqli_sql_exception","mysqli_driver","mysqli","mysqli_warning","mysqli_result"
					,"mysqli_stmt","DateTime","DateTimeZone"
				);
		foreach(get_declared_classes() as $class){
			if(!in_array($class,$std)) $result[] = $class;
		}
		return $result;
	}
}
?>