<?php

function c3_get_loaded_aws_sdk_version() {
	$const = get_defined_constants('user');
	return c3_check_aws_sdk_version($const);
}

function c3_check_aws_sdk_version($constants) {
	if (preg_grep('/AWS-2/', array_keys($constants['user']))) {
		return 'v2';
	}
	return 'v3';
}
