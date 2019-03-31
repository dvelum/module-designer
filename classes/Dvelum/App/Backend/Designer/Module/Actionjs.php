<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2019  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Dvelum\App\Backend\Designer\Module;
use Dvelum\App\Backend\Designer\Module;


class Actionjs extends Module
{
	/**
	 * Get ActionJs Code
	 */
	public function loadAction()
	{
		$project = $this->getProject();
		$this->response->success(['code'=>$project->getActionJs()]);
	}

	/**
	 * Save ActionJs code
	 */
	public function saveAction()
	{
		$code = $this->request->post('code', 'raw', false);

		if($code === false){
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

		$project = $this->getProject();
		$project->setActionJs($code);
		$this->storeProject();
		$this->response->success();
	}
}