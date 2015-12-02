<?php
global $CFG;

require_once($CFG->libdir.'/uploadlib.php');
require_once($CFG->libdir.'/filestorage/zip_packer.php');
require_once($CFG->dirroot.'/question/format.php');

require_once($CFG->libdir. '/ims/main.php');

/*
require_once dirname(__FILE__) .'/lib/debug_util.class.php';
require_once dirname(__FILE__) .'/lib/util.php';
require_once_all(dirname(__FILE__) .'/lib/*.class.php');

require_once dirname(__FILE__) .'/qti/reader/ims_xml_reader.class.php';
require_once_all(dirname(__FILE__) .'/qti/reader/*.class.php');

require_once dirname(__FILE__) .'/qti/writer/Ims_id_factory.class.php';
require_once dirname(__FILE__) .'/qti/writer/Ims_xml_writer.class.php';
require_once_all(dirname(__FILE__) .'/qti/writer/*.class.php');

require_once dirname(__FILE__) .'/qti/qti_resource_manager_base.class.php';
require_once dirname(__FILE__) .'/qti/qti_renderer_base.class.php';
require_once_all(dirname(__FILE__) .'/qti/*.class.php');

require_once dirname(__FILE__) .'/qti/import_strategy/qti_import_strategy_base.class.php';
require_once_all(dirname(__FILE__) .'/qti/import_strategy/*.class.php');

*/

require_once dirname(__FILE__) .'/moodle/builder/question_builder.class.php';
require_once dirname(__FILE__) .'/moodle/builder/numerical_builder_base.class.php';
require_once dirname(__FILE__) .'/moodle/builder/calculated_builder_base.class.php';
require_once dirname(__FILE__) .'/moodle/builder/calculated_builder.class.php';
require_once_all(dirname(__FILE__) .'/moodle/builder/*.class.php');

require_once dirname(__FILE__) .'/moodle/serializer/serializer_base.class.php';
require_once dirname(__FILE__) .'/moodle/serializer/question_serializer.class.php';
require_once dirname(__FILE__) .'/moodle/serializer/numerical_serializer_base.class.php';
require_once dirname(__FILE__) .'/moodle/serializer/calculated_serializer_base.class.php';
require_once_all(dirname(__FILE__) .'/moodle/serializer/*.class.php');

require_once_all(dirname(__FILE__) .'/moodle/*.class.php');

require_once dirname(__FILE__).'/qti_export.class.php';
require_once dirname(__FILE__).'/qti_import.class.php';
