<?php
namespace Riki\CsvOrderMultiple\Api\Data;

interface StatusInterface
{
    const IMPORT_WAITING = 0;
    const IMPORT_SUCCESS = 1;
    const IMPORT_FAIL    = 2;
    const IMPORT_PROCESSING    = 3;
}