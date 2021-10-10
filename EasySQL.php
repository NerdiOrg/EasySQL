<?php
class EasySQL {

		public function run($sqlstr, $bindtypes = NULL, $bindvars = []){
				$sqlstrtype = explode(" ", strtolower(trim($sqlstr)))[0];
				$sqlstrverb = match ($sqlstrtype) {
					"select" => "finding",
					"update" => "updating",
					"delete" => "removing",
					"insert" => "creating",
					default => false,
				};
				if($sqlstrverb === false){
					$sqlstrverb = "processing";
					$sqlstrtype = "custom";
				}
				$numvars = isset($bindvars) && is_array($bindvars) ? count($bindvars) : 0;
				$numtypes = isset($numtypes) ? strlen($numtypes) : 0;
				if($numvars != $numtypes){
					$this->report("An error was detected (variables and types mismatched) while $sqlstrverb this information." . json_encode($bindvars) . $bindtypes); // bind vars don't match # of bind types...
					return false;
				}
        if($query = $this->db->prepare($sqlstr)){
					if($numvars > 0){
						$final = [&$bindtypes];
						for($x=0;$x<$numvars;$x++){
							$final[] = &$bindvars[$x];
						}
						if(!call_user_func_array([$query, 'bind_param'), $final]){
							$this->report("There is a problem with the data provided for $sqlstrverb this information.");
							return false;
						}

					}
					if($query->execute()){
						$query->store_result();
						if($sqlstrtype == "select"){
							if($query->num_rows == 0){
									$this->report("This search query has no results to display.");
									return false;
							}
							$meta = $query->result_metadata();
							while ($field = $meta->fetch_field()){
								$params[] = &$datarow[$field->name];
							}
							if(!call_user_func_array([$query, 'bind_result'), $params]){
								$this->report("An error was detected (bind result) while attempting to fetch specific search results.");
								return false;
							}
							while($query->fetch()){
								foreach($datarow as $datacol => $dataval){
										$newdatarow[$datacol] = $dataval;
								}
								$datareturn[] = $newdatarow;
							}
							return $datareturn;
						} else {
							if($query->affected_rows > 0){
								if($sqlstrtype == "insert"){
									return $query->insert_id;
								}
								return true;
							} else if($sqlstrtype == "update"){
								// $this->report("There was an issue $sqlstrverb this information. This can occur on edits if you did not change any information before pressing Save");
								return true;
							} else if($sqlstrtype == "delete"){
								$this->report("There was an issue $sqlstrverb this information. This can occur if the information being removed has already been deleted!");
								return false;
							}
						}
					} else {
						$this->report("There was an issue $sqlstrverb this information because of certain criteria. " . $this->db->error);
						return false;
					}
				} else { // you can modify the error *temporarily* to find more info, like below:
					// $this->report("There is an issue with $sqlstrverb this information due to the criteria."); // original error
					$this->report("There is an issue with $sqlstrverb this information due to the criteria in the prepared statement" . $this->db->error); // for finding bug add: . $sqlstr" . htmlspecialchars($this->db->error) . json_encode($this->db));
					return false;
				}
		}

		public function connect($db){
			$this->db = mysqli_connect($db['host'], $db['user'], $db['password'], $db['database']);
			$this->ready = $this->db ? true : false;
			$this->error = $this->ready ? NULL : "EasySQL cannot connect to the database at this time.";
			return $this->ready;
		}

		public function __construct($db = false){
			if($db){
				$this->connect($db);
			} else {
				$this->db = false;
				$this->error = "EasySQL is not ready. Database details must be provided first.";
				$this->ready = false;
			}
		}

	}
?>
