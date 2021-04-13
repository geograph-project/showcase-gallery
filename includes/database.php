<?php

// General Database Functions, provides some abstraction, rather than using mysqli_* fnctions directly
// Created by Barry Hunter, for own use. Reused here with permission.

function dbQuote($in) {
	global $db;
	return "'".mysqli_real_escape_string($db,$in)."'";
}

function sqlMakeCountQuery(&$sql) {
	if (isset($sql['group'])) {
		if (isset($sql['having'])) {
			$sql['count_query'] = "SELECT COUNT(DISTINCT IF({$sql['having']},{$sql['group']},NULL))";
		} else {
			$sql['count_query'] = "SELECT COUNT(DISTINCT {$sql['group']})";
		}
		$sql['count_query'] = preg_replace('/\b(ASC|DESC)\b/i','',$sql['count_query']);
	} else {
		$sql['count_query'] = "SELECT count(*)";
	}
	if (isset($sql['tables']) && count($sql['tables'])) {
		$sql['count_query'] .= " FROM ".join(' ',$sql['tables']);
	}
	if (isset($sql['wheres']) && count($sql['wheres'])) {
		$sql['count_query'] .= " WHERE ".join(' AND ',$sql['wheres']);
	}
	return $sql['count_query'];
}



function sqlMakeQuery(&$sql) {
	if (is_array($sql['columns'])) {
		$sql['columns'] = join(',',$sql['columns']);
	}
	$sql['sql_query'] = "SELECT {$sql['columns']}";

	if (isset($sql['tables']) && count($sql['tables'])) {
		$sql['sql_query'] .= " FROM ".join(' ',$sql['tables']);
	}
	if (isset($sql['wheres']) && count($sql['wheres'])) {
		$sql['sql_query'] .= " WHERE ".join(' AND ',$sql['wheres']);
	}
	if (isset($sql['group'])) {
		$sql['sql_query'] .= " GROUP BY {$sql['group']}";
	}
	if (isset($sql['having'])) {
		$sql['sql_query'] .= " HAVING {$sql['having']}";
	}
	if (isset($sql['order'])) {
		$sql['sql_query'] .= " ORDER BY {$sql['order']}";
	}
	if (isset($sql['limit'])) {
		$sql['sql_query'] .= " LIMIT {$sql['limit']}";
	}
	return $sql['sql_query'];
}


function queryExecute($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error queryExecute: '.mysqli_error($db));
	return $result;
}

function getOne($query) {
	global $db;
	$result = mysqli_query($db, $query) or print("<br>Error getOne [[ $query ]] : ".mysqli_error($db));
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_row($result);
		return $row[0];
	} else {
		return FALSE;
	}
}

function getRow($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error getRow: '.mysqli_error($db));
	if (mysqli_num_rows($result)) {
		return mysqli_fetch_assoc($result);
	} else {
		return FALSE;
	}
}

function getCol($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error getColAsKeys: '.mysqli_error($db));
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	while($row = mysqli_fetch_row($result)) {
		$a[] = $row[0];
	}
	return $a;
}

function getColAsKeys($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error getColAsKeys: '.mysqli_error($db));
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	while($row = mysqli_fetch_row($result)) {
		$a[$row[0]] = '';
	}
	return $a;
}

function getAll($query) {
	global $db;

if (!empty($_GET['d']))
	die($query);

	$result = mysqli_query($db, $query) or print('<br>Error getAll: '.mysqli_error($db));
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	while($row = mysqli_fetch_assoc($result)) {
		$a[] = $row;
	}
	return $a;
}

function getAssoc2($table,$key,$value) {
	return getAssoc("SELECT $key, $value FROM $table");
}

function getAssoc($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error getAssoc: '.mysqli_error($db));
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	$row = mysqli_fetch_assoc($result);

	if (count($row) > 2) {
		do {
			$i = array_shift($row);
			$a[$i] = $row;
		} while($row = mysqli_fetch_assoc($result));
	} else {
		$row = array_values($row);
		do {
			$a[$row[0]] = $row[1];
		} while($row = mysqli_fetch_row($result));
	}
	return $a;
}

function getAssoc3($query) {
	global $db;
	$result = mysqli_query($db, $query) or print('<br>Error getAssoc: '.mysqli_error($db));
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	if (preg_match('/SELECT .*,.*,.* FROM/i',$query)) {
		while($row = mysqli_fetch_row($result)) {
			$i = array_shift($row);
			$a[$i] = $row;
		}
	} else {
		while($row = mysqli_fetch_row($result)) {
			$a[$row[0]] = $row[1];
		}
	}
	return $a;
}


####################

function updates_to_a(&$updates) {
	global $db;
	$a = array();
	foreach ($updates as $key => $value) {
		$key = str_replace('`','',$key); //ugly sql-injection protection!
		//NULL
		if (is_null($value)) {
			$a[] = "`$key`=NULL";
		} else {
			//converts uk dates to mysql format (mostly) - better than strtotime as it might not deal with uk dates
			if (preg_match('/^(\d{2})[ \/\.-]{1}(\d{2})[ \/\.-]{1}(\d{4})$/',$value,$m)) {
				$value = "{$m[3]}-{$m[2]}-{$m[1]}";
			}
			//numbers and functions, eg NOW()
			if (is_numeric($value) || preg_match('/^\w+\(\d*\)$/',$value)) {
				$a[] = "`$key`=$value";
			} else {
				$a[] = "`$key`='".mysqli_real_escape_string($db,$value)."'";
			}
		}
	}
	return $a;
}

function updates_to_insert($table,$updates) {
	$a = updates_to_a($updates);
	$table = str_replace('`','',$table); //ugly sql-injection protection!
	return "INSERT INTO `$table` SET ".join(',',$a);

}

function updates_to_update($table,$updates,$primarykey,$primaryvalue) {
	global $db;
	$a = updates_to_a($updates);
	$table = str_replace('`','',$table); //ugly sql-injection protection!
	$primarykey = str_replace('`','',$primarykey); //ugly sql-injection protection!
	if (!is_numeric($primaryvalue)) {
		$primaryvalue = "'".mysqli_real_escape_string($db,$primaryvalue)."'";
	}
	return "UPDATE `$table` SET ".join(',',$a)." WHERE `$primarykey` = $primaryvalue";

}
