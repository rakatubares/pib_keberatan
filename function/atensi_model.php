<?php

require 'model.php';

use voku\helper\StopWords;

class AtensiModel extends Model
{
	public function SaveAtensi()
	{
		$this->GetReff();
		$chunk_reff = array_chunk($this->reff, 100, true);
		foreach ($chunk_reff as $reff) {
			$this->Search($reff);
		}
	}

	private function GetReff()
	{
		$this->reff = $this->Select("
			SELECT 
				a.id, 
				a.npwp,
				a.kode_hs,
				a.keywords
			FROM 
				ref_pib_keberatan a
			WHERE
				a.keywords IS NOT null
			GROUP BY 
				2,3,4
			ORDER BY
				1
		");
	}

	private function Search($reff)
	{
		$this->search_result = [];
		
		foreach ($reff as $key => $value) {
			$result = $this->Select("
				SELECT
					pib.NO_AJU, 
					pib.NO_PIB,
					pib.TGL_PIB,
					pib.NPWP_IMPORTIR,
					pib.SERI_BARANG, 
					pib.URAIAN_BARANG, 
					pib.KODE_HS, 
					pib.BM,
					pib.KODE_FASILITAS,
					pib.KODE_FASILITAS_1,
					pib.no_sptnp_ref,
					pib.tgl_sptnp_ref,
					pib.no_dok_asal_ref,
					pib.tgl_dok_asal_ref,
					pib.seri_brg_ref,
					pib.kode_hs_ref,
					pib.uraian_barang_ref,
					pib.total_bm_sptnp_ref
				FROM(
					SELECT 
						a.NO_AJU, 
						a.NO_PIB,
						a.TGL_PIB,
						a.NPWP_IMPORTIR,
						a.SERI_BARANG, 
						a.URAIAN_BARANG, 
						a.KODE_HS, 
						a.BM,
						a.KODE_FASILITAS,
						a.KODE_FASILITAS_1,
						b.no_sptnp no_sptnp_ref,
						b.tgl_sptnp tgl_sptnp_ref,
						b.no_dok_asal no_dok_asal_ref,
						b.tgl_dok_asal tgl_dok_asal_ref,
						b.seri_brg seri_brg_ref,
						b.kode_hs kode_hs_ref,
						b.uraian_barang uraian_barang_ref,
						b.total_bm_sptnp total_bm_sptnp_ref
					FROM 
						data_dt.pib_detil a,
						db_semesta.ref_pib_keberatan b
					WHERE 
						b.id = " . $value['id'] . " and
						MATCH (a.URAIAN_BARANG) AGAINST ('" . $value['keywords'] . "' IN BOOLEAN MODE) and
						a.NPWP_IMPORTIR = b.npwp AND
						a.KODE_HS = b.kode_hs AND
						a.tgl_pib >= '2018-01-01'
				) pib
				LEFT JOIN
					data_dt.sptnp c
				ON 
					pib.NO_AJU = c.CAR
				WHERE
					c.NO_SPTNP is null
				ORDER BY
					pib.TGL_PIB,
					pib.NO_PIB,
					pib.SERI_BARANG
			");

			if (count($result) > 0) {
				$this->search_result = array_merge($this->search_result, $result);
			}
		}
		$chunk_result = array_chunk($this->search_result, 1000, true);
		foreach ($chunk_result as $r) {
			$this->Preprocess($r);
		}
		$this->search_result = null;
	}

	private function Preprocess($result)
	{
		foreach ($result as $key => $value) {
			$uraian_pib = $this->SplitWords($value['URAIAN_BARANG']);
			$uraian_ref = $this->SplitWords($value['uraian_barang_ref']);
			similar_text($uraian_ref, $uraian_pib, $percent);
			if ($percent > 80) {
				$this->input[] = array_values($value);
			}
		}
		$this->InsertData();
	}

	private function InsertData($value='')
	{
		$this->Insert("
			INSERT IGNORE INTO att_pib_keberatan (
				no_aju,
				no_pib,
				tgl_pib,
				npwp_importir,
				seri_brg,
				uraian_barang,
				kode_hs,
				bm,
				kode_fasilitas,
				kode_fasilitas_1,
				no_sptnp_ref,
				tgl_sptnp_ref,
				no_dok_asal_ref,
				tgl_dok_asal_ref,
				seri_brg_ref,
				kode_hs_ref,
				uraian_brg_ref,
				total_bm_sptnp_ref
			) 
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		", $this->input);

		$this->input = null;
	}

	private function SplitWords($words = '')
	{
		$excludeWords = array('baik', 'baru', 'brand', 'dan', 'height', 'invoice', 'kond', 'length', 'pce', 'pos', 'sesuai', 'untuk');
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
			$string = implode(' ', $notnum);
		} else {
			$string = '';
		}
		
		return $string;
	}
}