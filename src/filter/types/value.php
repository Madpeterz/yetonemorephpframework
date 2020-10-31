<?php
abstract class inputFilter_valueFilter extends inputFilter_filter_color
{
    public function valueFilter($value=null,string $filter,array $args = [])
	{
		$this->failure = false;
		if($value != null)
		{
			if(is_object($value) == true)
			{
				$this->failure = true;
				$this->whyfailed = "InputFilter can not deal with objects you crazy person";
				return null;
			}
			elseif(is_string($value) == true)
			{
				$this->testOK = TRUE;
				$fast_test_numbers = array("integer","double","float");
				if(in_array($filter,$fast_test_numbers) == true)
				{
					if(is_numeric($value) == false)
					{
						$this->failure = TRUE;
						$this->testOK = FALSE;
						$this->whyfailed = "Expects value to be numeric but its not";
					}
				}
				if($this->testOK == true)
				{
					if($filter == "string") $value = $this->filter_string($value, $args);
					else if($filter == "integer") $value = $this->filter_integer($value, $args);
					else if(($filter == "double") || ($filter == "float")) $value = $this->filter_float($value, $args);
					else if($filter == "checkbox") $value = $this->filter_checkbox($value, $args);
					else if($filter == "bool") $value = $this->filter_bool($value, $args);
					else if(($filter == "uuid") || ($filter == "key")) $value = $this->filter_uuid($value, $args);
					else if($filter == "vector") $value = $this->filter_vector($value, $args);
					else if($filter == "date") $value = $this->filter_date($value, $args);
					else if($filter == "email") $value = $this->filter_email($value, $args);
					else if($filter == "url") $value = $this->filter_url($value, $args);
					else if($filter == "color") $value = $this->filter_color($value, $args);
					else if($filter == "trueFalse") $value = $this->filter_trueFalse($value);
					else if($filter == "json") $value = $this->filter_json($value);
					if($value !== null) return $value;
					$this->failure = TRUE;
				}
			}
			else
			{
				if($filter == "array")
				{
					return $this->filter_array($value, $args);
				}
				else
				{
					$value = null;
					$this->whyfailed = "Type error expected a string but got somthing else";
				}
			}
		}
		else
		{
			$this->failure = TRUE;
		}
		if($this->failure == true)
		{
			if($filter == "checkbox") return 0;
            else if($filter == "trueFalse") return 0;
		}
		return null;
	}
    public function varFilter($currentvalue, string $filter="string", array $args = [])
    {
        return $this->valueFilter($currentvalue, $filter, $args);
    }
}
?>
