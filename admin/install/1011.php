<?php
	if (INSTALL_INIT == 'upgrade') {
		$current_version = '10.1.1';
		// upgrade language
		include (ROOT_PATH.'admin/install/updatelanguages.php');
	}
