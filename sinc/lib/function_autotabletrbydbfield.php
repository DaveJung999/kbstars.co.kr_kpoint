<?php

/*
	// 04/03/17 박선민 bugfix - 일부 ''을 ""으로

	필드에따라 자동으로 <tr>...</tr> 완성
	예) name필드가 최대 10자 가능한 경우
	html_autotabletrbydbfield($table,$uid)

*/
function html_autotabletrbydbfield($table, $uid = ""){
	$table_def_query = "SHOW FIELDS FROM `".db_escape($table)."`";
	$table_def_result = db_query($table_def_query);

	if (!$table_def_result){
		db_error('Error in table definition query: ');
		return;
	}

	$row = [];
	$result = null;
	if (!empty($uid)){
		$local_query = "SELECT * FROM `".db_escape($table)."` WHERE uid=" . db_escape($uid);
		$result		= db_query($local_query);
		if (!$result){
			db_error('Error in data query: ');
			return;
		}
		$row = db_array($result);
		if (!$row){
			back("해당 레코드가 없습니다.");
			return;
		}
	} else {
		$local_query = "SELECT * FROM `".db_escape($table)."` LIMIT 1";
		$result = db_query($local_query);
		if (!$result){
			db_error('Error in data query: ');
			return;
		}
	}


	/**
	 * Displays the form
	 */
	$timestamp_seen = (!empty($uid) ? 1 : 0);
	$fields_cnt = db_count($table_def_result);
	
	$fields_def = [];
	while ($field_def = db_array($table_def_result)){
		$fields_def[] = $field_def;
	}
	db_free($table_def_result);

	for ($i = 0; $i < $fields_cnt; $i++){
		$row_table_def = $fields_def[$i];
		$field = $row_table_def['Field'];

		if ($row_table_def['Type'] == 'datetime' && empty($row[$field])){
			$row[$field] = date('Y-m-d H:i:s', time());
		}

		$len = 0;
		if (preg_match('/\((\d+)\)/', $row_table_def['Type'], $matches)) {
			$len = $matches['1'];
		}
		
		$first_timestamp = 0;
		?>
		<tr>
			<td align="center"><?php echo htmlspecialchars($field); ?></td>
		<?php
		// The type column
		$is_binary = preg_match('/\s+binary/i', $row_table_def['Type']);
		$is_blob = preg_match('/blob/i', $row_table_def['Type']);
		$row_table_def['True_Type'] = preg_replace('/\s*\(.*/', '', $row_table_def['Type']);
		
		switch ($row_table_def['True_Type']){
			case 'set':
				$type = 'set';
				$type_nowrap = '';
				break;
			case 'enum':
				$type = 'enum';
				$type_nowrap = '';
				break;
			case 'timestamp':
				if (!$timestamp_seen) { // can only occur once per table
					$timestamp_seen = 1;
					$first_timestamp = 1;
				}
				$type = $row_table_def['Type'];
				break;
			default:
				$type = $row_table_def['Type'];
				$type_nowrap = ' nowrap="nowrap"';
				break;
		}

		// Prepares the field value
		$data = '';
		$special_chars = '';
		$backup_field = '';

		if (!empty($row)){
			// loic1: null field value
			if (!isset($row[$field])){
				$row[$field] = 'NULL';
				$special_chars = '';
				$data = $row[$field];
			} else {
				// loic1: special binary "characters"
				if ($is_binary || $is_blob){
					$row[$field] = str_replace("\x00", '\0', $row[$field]);
					$row[$field] = str_replace("\x08", '\b', $row[$field]);
					$row[$field] = str_replace("\x0a", '\n', $row[$field]);
					$row[$field] = str_replace("\x0d", '\r', $row[$field]);
					$row[$field] = str_replace("\x1a", '\Z', $row[$field]);
				} // end if
				$special_chars = htmlspecialchars($row[$field] ?? '');
				$data = $row[$field];
			} // end if... else...
			// loic1: if a timestamp field value is not included in an update
			//		statement MySQL auto-update it to the current timestamp
			$backup_field = ($row_table_def['True_Type'] == 'timestamp')
						? ''
						: '<input type="hidden" name="fields_prev[' . urlencode($field) . ']" value="' . urlencode($row[$field] ?? '') . '" />';

		} else {
			// loic1: display default values
			if (!isset($row_table_def['Default'])){
				$row_table_def['Default'] = '';
				$data = 'NULL';
			} else {
				$data = $row_table_def['Default'];
			}
			$special_chars = htmlspecialchars($row_table_def['Default'] ?? '');
			$backup_field = '';
		}


		// The value column (depends on type)
		// ----------------
		if (preg_match('/text/i', $row_table_def['True_Type'])){
			?>
			<td >
				<?php echo $backup_field . "\n"; ?>
				<textarea name="fields[<?php echo urlencode($field); ?>]" rows="10" cols="60" wrap="virtual"><?php echo $special_chars; ?></textarea>
			</td>
			<?php
		}
		else if (preg_match('/enum/i', $row_table_def['True_Type'])){
			$enum_str = str_replace('enum(', '', $row_table_def['Type']);
			$enum_str = preg_replace('/\\)$/', '', $enum_str);
			$enum = explode("','", substr($enum_str, 1, -1));
			$enum_cnt = count($enum);
			?>
			<td >
				<input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$enum$" />
			<?php
			echo "\n" . '					' . $backup_field;

			// show dropdown or radio depend on length
			if (strlen($row_table_def['Type']) > 5){
				echo "\n";
				?>
				<select name="field_<?php echo md5($field); ?>[]">
					<option value=""></option>
				<?php
				for ($j = 0; $j < $enum_cnt; $j++){
					// Removes automatic MySQL escape format
					$enum_atom = str_replace("''", "'", str_replace("\\\\", "\\", $enum[$j]));
					echo '					 ';
					echo '<option value="' . urlencode($enum_atom) . '"';
					if ($data == $enum_atom
						|| ($data == '' && (!isset($uid) || ($row_table_def['Null'] ?? '') != 'YES')
							 && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])){
						echo ' selected="selected"';
					}
					echo '>' . htmlspecialchars($enum_atom) . '</option>' . "\n";
				} // end for
				?>
				</select>
				<?php
			} // end if
			else {
				echo "\n";
				for ($j = 0; $j < $enum_cnt; $j++){
					// Removes automatic MySQL escape format
					$enum_atom = str_replace("''", "'", str_replace("\\\\", "\\", $enum[$j]));
					echo '					';
					echo '<input type="radio" name="field_' . md5($field) . '[]" value="' . urlencode($enum_atom) . '"';
					if ($data == $enum_atom
						|| ($data == '' && (!isset($uid) || ($row_table_def['Null'] ?? '') != 'YES')
							 && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])){
						echo ' checked="checked"';
					}
					echo ' />' . "\n";
					echo '					' . htmlspecialchars($enum_atom) . "\n";
				} // end for

			} // end else
			?>
			</td>
			<?php
		}
		else if (preg_match('/set/i', $row_table_def['True_Type'])){
			$set_str = str_replace('set(', '', $row_table_def['Type']);
			$set_str = preg_replace('/\\)$/', '', $set_str);
			$set = explode(',', $set_str);

			$vset = [];
			if (isset($data)) {
				foreach (explode(',', $data) as $k => $val){
					$vset[trim($val, " '")] = 1;
				}
			}
			$size = min(4, count($set));
			?>
			<td bgcolor="<?php echo $bgcolor; ?>">
				<?php echo $backup_field . "\n"; ?>
				<input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$set$" />
				<select name="field_<?php echo md5($field); ?>[]" size="<?php echo $size; ?>" multiple="multiple">
			<?php
		
			$countset = count($set);
			for ($j = 0; $j < $countset;$j++){
				$subset = trim($set[$j], " '");
				// Removes automatic MySQL escape format
				$subset = str_replace("''", "'", str_replace("\\\\", "\\", $subset));
				echo '					 ';
				echo '<option value="'. urlencode($subset) . '"';
				if (isset($vset[$subset]) && $vset[$subset]){
					echo ' selected="selected"';
				}
				echo '>' . htmlspecialchars($subset) . '</option>' . "\n";
			} // end for
			?>
				</select>
			</td>
			<?php
		}
		// Change by Bernard M. Piller <bernard@bmpsystems.com>
		// We don't want binary data destroyed
		else if ($is_binary || $is_blob){
			back("표시할 수 없는 데이터(바이너스, 파일데이터) 필드가 있습니다.");
		} // end else if
		else {
			if ($len < 4){
				$fieldsize = $maxlength = 4;
			} else {
				$fieldsize = (($len > 40) ? 40 : $len);
				$maxlength = $len;
			}
			echo "\n";
			?>
			<td bgcolor="<?php echo $bgcolor; ?>">
				<?php echo $backup_field . "\n"; ?>
				<input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>"	/>
			</td>
			<?php
		}
		?>
		</tr>
		<?php
	} // end for
} // end function

?>