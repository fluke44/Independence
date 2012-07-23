<?php

interface IDAL {
	function OpenRecordset($aTable, $aFields, $aSearch, $aSort = NULL);
	function UpdateRecordset($aTable, $aRecords, $aSearch = null);
	function InsertRecordset($asTable, $arRecords, $apSearch);
	function RunQuery($aQuery);
	function CallSP($spname, $aSearch);
	function GenerateSelectStatement($aTable, $aFields = NULL, $aSearch = NULL, $aSort = NULL);
	function GenerateUpdateStatement($aTable, $aParams, $aSearch);
	function GenerateInsertStatement($asTable, $arData);
	function isStoredProcedure($aTable);
	function RetrieveFields($asTable);
	function FieldsToInsertForQuery($asTable, $arRecordset);
}