<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
        $this->load->library('Basic');
        $data['files'] = $this->basic->getOpenFiles();
		$this->load->view('editor.php', $data);
	}
}
