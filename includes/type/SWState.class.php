<?php
final class SWState {
	const LOG_NOACTION	= 0x00000001;
	const LOG_CREATED	= 0x00000010;
	const LOG_EDITED	= 0x00000100;
	const LOG_DELETED	= 0x00001000;
	const LOG_UNKNOWN	= 0x00000000;

	// ensures that this class acts like an enum
	// and that it cannot be instantiated
	private function __construct(){
	}
}
