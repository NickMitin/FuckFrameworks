<?php

  final class bmDataObjectMapCache extends bmCustomCache
  {
    
    private function getPropertyNameByFieldName($fieldName)
    {

      return $fieldName;
      
    }
    
    private function mysqlTypeToFFType($mysqlType)
    {
      if (mb_strpos($mysqlType, 'int') === 0)
      {
        $result = 'BM_VT_INTEGER';
      }
      elseif (mb_strpos($mysqlType, 'float') === 0)
      {
        $result = 'BM_VT_FLOAT';
      }
      elseif (mb_strpos($mysqlType, 'date') === 0)
      {
        $result = 'BM_VT_DATETIME';
      }
      elseif (mb_strpos($mysqlType, 'char') !== false)
      {
        $result = 'BM_VT_STRING';
      }
      elseif (mb_strpos($mysqlType, 'text') !== false)
      {
        $result = 'BM_VT_STRING';
      }
      else
      {
        $result = 'BM_VT_ANY';
      }
      return $result;
      
    }
    
    public function getFields(bmDataObjectMap $dataObjectMap)
    {
      
      $result = $this->application->cacheLink->get('dataObject_' . $dataObjectMap->identifier);
      $result = false;
      if ($result === false)
      {
        
        $qTableFields = $this->application->dataLink->select("DESCRIBE `" . $dataObjectMap->name . "`;");
        $result = array();
        while ($tableField = $qTableFields->nextObject())
        {
          $tableField->Property = $this->getPropertyNameByFieldName($tableField->Field);
          $tableField->FFType = $this->mysqlTypeToFFType($tableField->Type);
          if ($tableField->Default === null)
          {
            switch ($tableField->FFType)
            {
              case 'BM_VT_INTEGER':
              case 'BM_VT_FLOAT':
                $tableField->FFDefault = 0;
              break;
              case 'BM_VT_STRING':
                $tableField->FFDefault = "''";
              break;
              case 'BM_VT_DATETIME':
                $tableField->FFDefault = "'0000-01-01 00:00:00'";
              break;
              case 'BM_VT_ANY':
                $tableField->FFDefault = "''";
              break;
            }
          }
          else
          {
            $tableField->FFDefault = $tableField->Default;
          }
          $result[$tableField->Field] = $tableField;
          
        }
        $this->application->cacheLink->set('dataObject_' . $dataObjectMap->identifier, $result);
      }
      return $result;
    }  
  }

?>