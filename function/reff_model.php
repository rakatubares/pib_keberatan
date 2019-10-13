<?php

require 'model.php';

use voku\helper\StopWords;

/**
 * get currencies data from api url
 */
class ReffModel extends Model
{
	private function GetData()
	{
		$this->data = $this->Select("
			SELECT a.id, a.uraian_barang
			FROM db_semesta.ref_pib_keberatan a
		");
	}

	private function SplitWords($words = '')
	{
		$stopWords = new StopWords();
		$en_words = $stopWords->getStopWordsFromLanguage('en');
		$id_words = $stopWords->getStopWordsFromLanguage('id');
		$excludeWords = array('baik', 'baru', 'brand', 'dan', 'height', 'invoice', 'kond', 'length', 'pce', 'pos', 'sesuai', 'untuk');
		$excludeWords = array_merge($excludeWords, $en_words, $id_words);
		foreach ($excludeWords as $key => $value) {
			$excludeWords[$value] = 1;
			unset($excludeWords[$key]);
		}

		$lower = strtolower($words);
		$replace = preg_replace('/[^\da-z]/i', ' ', $lower); // REPLACE NON ALPHANUMERIC INTO SPACE
		$split = preg_split("/[^\w]*([\s]+[^\w]*|$)/", $replace, -1, PREG_SPLIT_NO_EMPTY); // SPLIT STRING INTO ARRAY BY SPACE
		$notnum = array_filter($split, function ($var) use ($excludeWords) { 
			return (preg_match('/\d/', $var) == 0)
			&& ((strlen($var) > 2) || (isset($includeWords[strtolower($var)])))
			&& (!isset($excludeWords[strtolower($var)]));
		}); // FILTER : GET WORDS WITHOUT NUMBER, WORDS WITH CHAR > 2, EXCLUDE STOPWORDS ETC, INCLUDE PREDEFINED WORDS
		if (count($notnum) > 0) {
			$string = '+' . implode(' +', $notnum);
		} else {
			$string = '';
		}
		
		return $string;
	}

	public function Preprocess()
	{
		$this->GetData();

		foreach ($this->data as $key => $value) {
			if ($this->SplitWords($value['uraian_barang']) <> '') {

				// $this->HsBtki($value['hs_penetapan']);

				$this->input[] = [
					'id' => $value['id'],
					'word' => $this->SplitWords($value['uraian_barang'])
				];
			}
		}

		return $this->input;

	}

	public function InsertData()
	{
		$this->Preprocess();

		$this->Insert("UPDATE db_semesta.ref_pib_keberatan SET keywords=:word WHERE id=:id", $this->input);

	}
}