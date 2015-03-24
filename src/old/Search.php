<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-25 11:12:50
/*	Updated: UTC 2015-02-05 06:52:34
/*
/* ************************************************************************** */
namespace Loli;
//http://codepoints.net/basic_multilingual_plane
class Search{


	public $query = '';

	// utf-32 可以选择不用 补 0
	// 替换 a[] = [int = 动态 utf-32的10进制代码,否则 = 静态文字 utf-32, 替换的文字 utf-32 开始, 替换的文字 utf-32 结束 (可选)];
	public $replace = [

		//	3
		['0073', '017F'],





		[65248, 'FF01', 'FF5E'],

		[119743, '1D400', '1D419'],

		[119737, '1D41A', '1D433'],

		[119795, '1D434', '1D44D'],

		[119789, '1D44E', '1D454'],

		[119789, '1D456', '1D467'],

		[119847, '1D468', '1D481'],

		[119841, '1D482', '1D49B'],

		[119867, '1D49C'],
		[119867, '1D49E', '1D49F'],
		[119867, '1D4A2'],
		[119867, '1D4A5', '1D4A6'],
		[119867, '1D4A9', '1D4AC'],
		[119867, '1D4AE', '1D4B5'],

		[119893, '1D4B6', '1D4B9'],
		[119893, '1D4BB'],
		[119893, '1D4BD', '1D4C3'],
		[119893, '1D4C5', '1D4CF'],

		[119951, '1D4D0', '1D4E9'],

		[119945, '1D4EA', '1D503'],

		[120003, '1D504', '1D505'],
		[120003, '1D507', '1D50A'],
		[120003, '1D50D', '1D514'],
		[120003, '1D516', '1D51C'],

		[119997, '1D51E', '1D537'],

		[120055, '1D538', '1D539'],
		[120055, '1D53B', '1D53E'],
		[120055, '1D540', '1D544'],
		[120055, '1D546'],
		[120055, '1D54A', '1D550'],

		[120049, '1D552', '1D56B'],

		[120107, '1D56C', '1D585'],

		[120101, '1D586', '1D59F'],

		[120159, '1D5A0', '1D5B9'],

		[120153, '1D5BA', '1D5D3'],

		[120211, '1D5D4', '1D5ED'],

		[120205, '1D5EE', '1D607'],

		[120263, '1D608', '1D621'],

		[120205, '1D622', '1D63B'],

		[120257, '1D63C', '1D655'],

		[120309, '1D656', '1D66F'],

		[120367, '1D670', '1D689'],

		[120361, '1D68A', '1D6A3'],

		[120734, '1D7CE', '1D7D7'],

		[120744, '1D7D8', '1D7E1'],

		[120754, '1D7E2', '1D7EB'],

		[120764, '1D7EC', '1D7F5'],

		[120774, '1D7F6', '1D7FF'],



	];

	// 匹配 a[] = [utf-32代码 开始, utf-32代码 结束 (可选)];
	public $match = [

		//	1	http://codepoints.net/basic_latin
		['0030', '0039'],
		['0041', '005A'],
		['0061', '007A'],

		//	2	http://codepoints.net/latin-1_supplement
		['00B2', '00B3'],
		['00B5'],
		['00B9', '00BA'],
		['00BC', '00BE'],
		['00C0', '00D6'],
		['00D8', '00F6'],
		['00F8', '00FF'],

		// 3	http://codepoints.net/latin_extended-a
		['0100', '017F'],

		// 4	http://codepoints.net/latin_extended-b
		['0180', '024F'],

		// 5	http://codepoints.net/ipa_extensions
		['0250', '02AF'],

		// 6	http://codepoints.net/spacing_modifier_letters
		['02B0', '02C1'],
		['02C6', '02D1'],
		['02E0', '02E4'],
		['02EC'],
		['02EE'],

		// 7	http://codepoints.net/combining_diacritical_marks
		['0300', '036F'],

		// 8	http://codepoints.net/greek_and_coptic
		['0370', '0374'],
		['0375', '0377'],
		['037A', '037D'],
		['0386'],
		['0388', '038A'],
		['038C'],
		['038E', '03A1'],
		['03A3', '03FF'],

		// 9	http://codepoints.net/cyrillic
		['0400', '0481'],
		['0483', '04FF'],

		// 10	http://codepoints.net/cyrillic_supplement
		['0500', '0527'],

		// 11	http://codepoints.net/armenian
		['0531', '0556'],
		['0559'],
		['0561', '0587'],

		// 12	http://codepoints.net/hebrew
		['0591', '05BD'],
		['05BF'],
		['05C1', '05C2'],
		['05C4', '05C5'],
		['05C7'],
		['05D0', '05EA'],
		['05F0', '05F2'],

		// 13	http://codepoints.net/arabic
		['0610', '061A'],
		['0620', '0669'],
		['066E', '06DC'],
		['06DF', '06E8'],
		['06EA', '06FC'],
		['06FF'],

		// 14	http://codepoints.net/syriac
		['0710', '074A'],
		['074D', '074F'],

		// 15	http://codepoints.net/arabic_supplement
		['0750', '077F'],

		// 16	http://codepoints.net/thaana
		['0780', '07B1'],

		// 17	http://codepoints.net/nko
		['07C0', '07F5'],
		['07FA'],

		// 18	http://codepoints.net/samaritan
		['0800', '082D'],

		// 19	http://codepoints.net/mandaic
		['0840', '085B'],

		// 20	http://codepoints.net/arabic_extended-a
		['08A0'],
		['08A2', '08AC'],
		['08E4', '08FE'],

		// 21	http://codepoints.net/devanagari
		['0900', '0963'],
		['0966', '096A'],
		['0971', '0977'],
		['0979', '097F'],

		// 22	http://codepoints.net/bengali
		['0981', '0983'],
		['0985', '098C'],
		['098F', '0990'],
		['0993', '09A8'],
		['09AA', '09B0'],
		['09B2'],
		['09B6', '09B9'],
		['09BC', '09C4'],
		['09C7', '09C8'],
		['09CB', '09CE'],
		['09D7'],
		['09DC', '09DD'],
		['09DF', '09E3'],
		['09E6', '09F1'],
		['09F4', '09F9'],

		// 23	http://codepoints.net/gurmukhi
		['0A01', '0A03'],
		['0A05', '0A0A'],
		['0A0F', '0A10'],
		['0A13', '0A28'],
		['0A2A', '0A30'],
		['0A32', '0A33'],
		['0A35', '0A36'],
		['0A38', '0A39'],
		['0A3C'],
		['0A3E', '0A42'],
		['0A47', '0A48'],
		['0A4B', '0A4D'],
		['0A51'],
		['0A59', '0A5C'],
		['0A5E'],
		['0A66', '0A75'],

		// 24	http://codepoints.net/gujarati
		['0A81', '0A83'],
		['0A85', '0A8D'],
		['0A8F', '0A91'],
		['0A93', '0AA8'],
		['0AAA', '0AB0'],
		['0AB2', '0AB3'],
		['0AB5', '0AB9'],
		['0ABC', '0AC5'],
		['0AC7', '0AC9'],
		['0ACB', '0ACD'],
		['0AD0'],
		['0AE0', '0AE3'],
		['0AE6', '0AEF'],

		// 25	http://codepoints.net/oriya
		['0B01', '0B03'],
		['0B05', '0B0C'],
		['0B0F', '0B10'],
		['0B13', '0B28'],
		['0B2A', '0B30'],
		['0B32', '0B33'],
		['0B35', '0B39'],
		['0B3C', '0B44'],
		['0B47', '0B48'],
		['0B4B', '0B4D'],
		['0B56', '0B57'],
		['0B5C', '0B5D'],
		['0B5F', '0B63'],
		['0B66', '0B6F'],
		['0B71', '0B77'],

		// 26	http://codepoints.net/tamil
		['0B82', '0B83'],
		['0B85', '0B8A'],
		['0B8E', '0B90'],
		['0B92', '0B95'],
		['0B99', '0B9A'],
		['0B9C'],
		['0B9E', '0B9F'],
		['0BA3', '0BA4'],
		['0BA8', '0BAA'],
		['0BAE', '0BB9'],
		['0BBE', '0BC2'],
		['0BC6', '0BC8'],
		['0BCA', '0BCD'],
		['0BD0'],
		['0BD7'],
		['0BE6', '0BF2'],

		// 27	http://codepoints.net/telugu
		['0C01', '0C03'],
		['0C05', '0C0C'],
		['0C0E', '0C10'],
		['0C12', '0C28'],
		['0C2A', '0C33'],
		['0C35', '0C39'],
		['0C3D', '0C44'],
		['0C46', '0C48'],
		['0C4A', '0C4D'],
		['0C55', '0C56'],
		['0C58', '0C59'],
		['0C60', '0C63'],
		['0C66', '0C6F'],
		['0C78', '0C7E'],

		// 28	http://codepoints.net/kannada
		['0C82', '0C83'],
		['0C85', '0C8C'],
		['0C8E', '0C90'],
		['0C92', '0CA8'],
		['0CAA', '0CB3'],
		['0CB5', '0CB9'],
		['0CBC', '0CC4'],
		['0CC6', '0CC8'],
		['0CCA', '0CCD'],
		['0CD5', '0CD6'],
		['0CDE'],
		['0CE0', '0CE3'],
		['0CE6', '0CEF'],
		['0CF1', '0CF2'],

		// 29	http://codepoints.net/malayalam
		['0D02', '0D03'],
		['0D05', '0D0C'],
		['0D0E', '0D10'],
		['0D12', '0D3A'],
		['0D3D', '0D44'],
		['0D46', '0D48'],
		['0D4A', '0D4E'],
		['0D57'],
		['0D60', '0D63'],
		['0D66', '0D75'],
		['0D79', '0D7F'],

		// 30	http://codepoints.net/sinhala
		['0D82', '0D83'],
		['0D85', '0D96'],
		['0D9A', '0DB1'],
		['0DB3', '0DBB'],
		['0DBD'],
		['0DC0', '0DC6'],
		['0DCA'],
		['0DCF', '0DD4'],
		['0DD6'],
		['0DD8', '0DDF'],
		['0DF2', '0DF3'],

		// 31	http://codepoints.net/thai
		['0E01', '0E3A'],
		['0E40', '0E4E'],
		['0E50', '0E59'],

		// 32	http://codepoints.net/lao
		['0E81', '0E82'],
		['0E84'],
		['0E87', '0E88'],
		['0E8A'],
		['0E8D'],
		['0E94', '0E97'],
		['0E99', '0E9F'],
		['0EA1', '0EA3'],
		['0EA5'],
		['0EA7'],
		['0EAA', '0EAB'],
		['0EAD', '0EB9'],
		['0EBB', '0EBD'],
		['0EC0', '0EC4'],
		['0EC6'],
		['0EC8', '0ECD'],
		['0ED0', '0ED9'],
		['0EDC', '0EDF'],

		// 33	http://codepoints.net/tibetan
		['0F00'],
		['0F18', '0F19'],
		['0F20', '0F33'],
		['0F35'],
		['0F37'],
		['0F39'],
		['0F3E', '0F47'],
		['0F49', '0F6C'],
		['0F71', '0F84'],
		['0F86', '0F97'],
		['0F99', '0FBC'],

		// 34	http://codepoints.net/myanmar
		['1000', '1049'],
		['1050', '109D'],

		// 35	http://codepoints.net/georgian
		['10A0', '10C5'],
		['10C7'],
		['10CD'],
		['10D0', '10FA'],
		['10FC', '10FF'],

		// 36	http://codepoints.net/hangul_jamo
		['1100', '11FF'],

		// 37	http://codepoints.net/ethiopic
		['1200', '1248'],
		['124A', '124D'],
		['1250', '1256'],
		['1258'],
		['125A', '125D'],
		['1260', '1288'],
		['128A', '128D'],
		['1290', '12B0'],
		['12B2', '12B5'],
		['12B8', '12BE'],
		['12C0'],
		['12C2', '12C5'],
		['12C8', '12D6'],
		['12D8', '1310'],
		['1312', '1315'],
		['1318', '135A'],
		['135D', '135F'],
		['1369', '137C'],

		// 38	http://codepoints.net/ethiopic_supplement
		['1380', '138F'],

		// 39	http://codepoints.net/cherokee
		['13A0', '13F4'],

		// 40	http://codepoints.net/unified_canadian_aboriginal_syllabics
		['1401', '166C'],
		['166E', '167F'],

		// 41	http://codepoints.net/ogham
		['1681', '169A'],

		// 42	http://codepoints.net/runic
		['16A0', '16EB'],
		['16EE', '16F0'],

		// 43	http://codepoints.net/tagalog
		['1700', '170C'],
		['170E', '1714'],

		// 44	http://codepoints.net/hanunoo
		['1720', '1734'],

		// 45	http://codepoints.net/buhid
		['1740', '1753'],

		// 46	http://codepoints.net/tagbanwa
		['1760', '176C'],
		['176E', '1770'],
		['1772', '1773'],

		// 47	http://codepoints.net/khmer
		['1780', '17D3'],
		['17D7'],
		['17DC', '17DD'],
		['17E0', '17E9'],
		['17F0', '17F9'],

		// 48	http://codepoints.net/mongolian
		['1810', '1819'],
		['1820', '1877'],
		['1880', '18AA'],

		// 49	http://codepoints.net/unified_canadian_aboriginal_syllabics_extended
		['18B0', '18F5'],

		// 50	http://codepoints.net/limbu
		['1900', '191C'],
		['1920', '192B'],
		['1930', '193B'],
		['1946', '194F'],

		// 51	http://codepoints.net/tai_le
		['1950', '196D'],
		['1970', '1974'],

		// 52	http://codepoints.net/new_tai_lue
		['1980', '19AB'],
		['19B0', '19C9'],
		['19D0', '19DA'],

		// 53	http://codepoints.net/khmer_symbols

		// 54	http://codepoints.net/buginese
		['1A00', '1A1B'],

		// 55	http://codepoints.net/tai_tham
		['1A20', '1A5E'],
		['1A60', '1A7C'],
		['1A7F', '1A89'],
		['1A90', '1A99'],

		// 56	http://codepoints.net/balinese
		['1B00', '1B4B'],
		['1B50', '1B7C'],

		// 57	http://codepoints.net/sundanese
		['1B80', '1BBF'],

		// 58	http://codepoints.net/batak
		['1BC0', '1BF3'],

		// 59	http://codepoints.net/lepcha
		['1C00', '1C37'],
		['1C40', '1C49'],
		['1C4D', '1C4F'],

		// 60	http://codepoints.net/ol_chiki
		['1C50', '1C7D'],

		// 61	http://codepoints.net/sundanese_supplement

		// 62	http://codepoints.net/vedic_extensions
		['1CD0', '1CD2'],
		['1CD4', '1CF6'],

		// 63	http://codepoints.net/phonetic_extensions
		['1D00', '1D7F'],

		// 64	http://codepoints.net/phonetic_extensions_supplement
		['1D80', '1DBF'],

		// 65	http://codepoints.net/combining_diacritical_marks_supplement
		['1DC0', '1DE6'],
		['1DFC', '1DFF'],

		// 66	http://codepoints.net/latin_extended_additional
		['1E00', '1EFF'],

		// 67	http://codepoints.net/greek_extended
		['1F00', '1F15'],
		['1F18', '1F1D'],
		['1F20', '1F45'],
		['1F48', '1F4D'],
		['1F50', '1F57'],
		['1F59'],
		['1F5B'],
		['1F5D'],
		['1F5F', '1F7D'],
		['1F80', '1FB4'],
		['1FB6', '1FBD'],
		['1FBE'],
		['1FC2', '1FC4'],
		['1FC6', '1FCC'],
		['1FD0', '1FD3'],
		['1FD6', '1FDB'],
		['1FE0', '1FEC'],
		['1FF2', '1FF4'],
		['1FF6', '1FFC'],

		// 68	http://codepoints.net/general_punctuation
		['2018', '2019'],

		// 69	http://codepoints.net/superscripts_and_subscripts
		['20D0', '20F0'],

		// 70	http://codepoints.net/currency_symbols


		// 71	http://codepoints.net/combining_diacritical_marks_for_symbols
		['20D0', '20F0'],

		// 72	http://codepoints.net/letterlike_symbols
		['2100', '214F'],

		// 73	http://codepoints.net/number_forms
		['2150', '2189'],

		// 74	http://codepoints.net/arrows

		// 75	http://codepoints.net/mathematical_operators

		// 76	http://codepoints.net/miscellaneous_technical

		// 77	http://codepoints.net/control_pictures

		// 78	http://codepoints.net/optical_character_recognition

		// 79	http://codepoints.net/enclosed_alphanumerics
		['2460', '249B'],
		['24EA', '24FF'],

		// 80	http://codepoints.net/box_drawing

		// 81	http://codepoints.net/block_elements

		// 82	http://codepoints.net/geometric_shapes

		// 83	http://codepoints.net/miscellaneous_symbols

		// 84	http://codepoints.net/dingbats

		// 85	http://codepoints.net/miscellaneous_mathematical_symbols-a

		// 86	http://codepoints.net/supplemental_arrows-a

		// 87	http://codepoints.net/braille_patterns

		// 88	http://codepoints.net/supplemental_arrows-b

		// 89	http://codepoints.net/miscellaneous_mathematical_symbols-b

		// 90	http://codepoints.net/supplemental_mathematical_operators

		// 91	http://codepoints.net/miscellaneous_symbols_and_arrows

		// 92	http://codepoints.net/glagolitic
		['2C00', '2C2E'],
		['2C30', '2C5E'],

		// 93	http://codepoints.net/latin_extended-c
		['2C60', '2C7F'],

		// 94	http://codepoints.net/coptic
		['2C80', '2CE4'],
		['2CEB', '2CF3'],
		['2CFD'],

		// 95	http://codepoints.net/georgian_supplement
		['2D00', '2D25'],
		['2D27'],
		['2D2D'],

		// 96	http://codepoints.net/tifinagh
		['2D30', '2D67'],
		['2D6F'],
		['2D7F'],

		// 97	http://codepoints.net/ethiopic_extended
		['2D80', '2D96'],
		['2DA0', '2DA6'],
		['2DA8', '2DAE'],
		['2DB0', '2DB6'],
		['2DB8', '2DBE'],
		['2DC0', '2DC6'],
		['2DC8', '2DCE'],
		['2DD0', '2DD6'],
		['2DD8', '2DDE'],

		// 98	http://codepoints.net/cyrillic_extended-a
		['2DE0', '2DFF'],

		// 99	http://codepoints.net/supplemental_punctuation

		// 100	http://codepoints.net/cjk_radicals_supplement
		['2E80', '2EF3'],

		// 101	http://codepoints.net/kangxi_radicals
		['2F00', '2FD5'],

		// 102	http://codepoints.net/ideographic_description_characters

		// 103	http://codepoints.net/cjk_symbols_and_punctuation
		['3005', '3007'],
		['3021', '302F'],
		['3031', '3035'],
		['3038', '303C'],

		// 104	http://codepoints.net/hiragana	平假名
		['3041', '3096'],
		['3099', '309F'],

		// 105 http://codepoints.net/katakana 	片假名
		['30A0', '30FA'],
		['30FC', '30FF'],

		// 106 http://codepoints.net/bopomofo 	注音
		['3105', '312D'],


		// 107	http://codepoints.net/hangul_compatibility_jamo
		['3220', '3229'],
		['3248', '324F'],
		['3251', '325F'],
		['3280', '3289'],
		['32B1', '32BF'],

		// 108	http://codepoints.net/kanbun
		['1392', '1395'],

		// 109	http://codepoints.net/bopomofo_extended
		['31A0', '31BA'],

		// 110	http://codepoints.net/cjk_strokes

		// 111	http://codepoints.net/katakana_phonetic_extensions
		['31F0', '31FF'],

		// 112	http://codepoints.net/enclosed_cjk_letters_and_months
		['3200', '321E'],
		['3220', '32FE'],

		// 113	http://codepoints.net/cjk_compatibility

		// 114	http://codepoints.net/cjk_unified_ideographs_extension_a
		['3400', '4DB5'],

		// 115	http://codepoints.net/yijing_hexagram_symbols

		// 116	http://codepoints.net/cjk_unified_ideographs
		['4E00', '9FCC'],

		// 117	http://codepoints.net/yi_syllables
		['A000', 'A48C'],

		// 118	http://codepoints.net/yi_radicals

		// 119	http://codepoints.net/lisu
		['A4D0', 'A4FD'],

		// 120	http://codepoints.net/vai
		['A500', 'A60C'],
		['A610', 'A62B'],

		// 121	http://codepoints.net/cyrillic_extended-b
		['A640', 'A672'],
		['A674', 'A67D'],
		['A67F', 'A697'],
		['A69F'],

		// 122	http://codepoints.net/bamum
		['A6A0', 'A6F1'],

		// 123	http://codepoints.net/modifier_tone_letters
		['A717', 'A71F'],

		// 124	http://codepoints.net/latin_extended-d
		['A722', 'A788'],
		['A78B', 'A78E'],
		['A790', 'A793'],
		['A7A0', 'A7AA'],
		['A7F8', 'A7FF'],

		// 125	http://codepoints.net/syloti_nagri
		['A800', 'A827'],

		// 126	http://codepoints.net/common_indic_number_forms
		['A830', 'A835'],

		// 127	http://codepoints.net/phags-pa
		['A840', 'A873'],

		// 128	http://codepoints.net/saurashtra
		['A880', 'A8C4'],
		['A8D0', 'A8D9'],

		// 129	http://codepoints.net/devanagari_extended
		['A8E0', 'A8F7'],
		['A8FB'],

		// 130	http://codepoints.net/kayah_li
		['A900', 'A92D'],

		// 131	http://codepoints.net/rejang
		['A930', 'A953'],

		// 132	http://codepoints.net/hangul_jamo_extended-a
		['A960', 'A97C'],

		// 133	http://codepoints.net/javanese
		['A980', 'A9C0'],
		['A9CF', 'A9D9'],

		// 134	http://codepoints.net/cham
		['AA00', 'AA36'],
		['AA40', 'AA4D'],
		['AA50', 'AA59'],

		// 135	http://codepoints.net/myanmar_extended-a
		['AA60', 'AA76'],
		['AA7A', 'AA7B'],

		// 136	http://codepoints.net/tai_viet
		['AA80', 'AAC2'],
		['AADB', 'AADD'],

		// 137	http://codepoints.net/meetei_mayek_extensions
		['AAE0', 'AAEF'],
		['AAF2', 'AAF6'],

		// 138	http://codepoints.net/ethiopic_extended-a
		['AB01', 'AB06'],
		['AB09', 'AB0E'],
		['AB11', 'AB16'],
		['AB20', 'AB26'],
		['AB28', 'AB2E'],

		// 139	http://codepoints.net/meetei_mayek
		['ABC0', 'ABEA'],
		['ABEC', 'ABED'],
		['ABF0', 'ABF9'],

		// 140	http://codepoints.net/hangul_syllables
		['AC00', 'D7A3'],

		// 141	http://codepoints.net/hangul_jamo_extended-b
		['D7B0', 'D7C6'],
		['D7CB', 'D7FB'],

		// 142	http://codepoints.net/high_surrogates


		// 143	http://codepoints.net/high_private_use_surrogates

		// 144	http://codepoints.net/low_surrogates

		// 145	http://codepoints.net/private_use_area

		// 146	http://codepoints.net/cjk_compatibility_ideographs
		['F900', 'FA6D'],
		['FA6F', 'FAD9'],

		// 147	http://codepoints.net/alphabetic_presentation_forms
		['FB00', 'FB06'],
		['FB13', 'FB17'],
		['FB1D', 'FB28'],
		['FB2A', 'FB36'],
		['FB38', 'FB3C'],
		['FB3E'],
		['FB40', 'FB41'],
		['FB43', 'FB44'],
		['FB46', 'FB4F'],

		// 148	http://codepoints.net/arabic_presentation_forms-a
		['FB50', 'FBC1'],
		['FBD3', 'FD3D'],
		['FD50', 'FD8F'],
		['FD92', 'FDC7'],
		['FDF0', 'FDFB'],

		// 149	http://codepoints.net/variation_selectors

		// 150	http://codepoints.net/vertical_forms

		// 151	http://codepoints.net/combining_half_marks
		['FE20', 'FE26'],

		// 152	http://codepoints.net/cjk_compatibility_forms

		// 153	http://codepoints.net/small_form_variants

		// 154	http://codepoints.net/arabic_presentation_forms-b
		['FE70', 'FE72'],
		['FE76', 'FEFC'],

		// 155	http://codepoints.net/halfwidth_and_fullwidth_forms
		['FF66', 'FFBE'],
		['FFC2', 'FFC7'],
		['FFCA', 'FFCF'],
		['FFD2', 'FFD7'],
		['FFDA', 'FFDC'],

		// 156	http://codepoints.net/specials











		// 1	http://codepoints.net/linear_b_syllabary
		['10000', '1000B'],
		['1000D', '10026'],
		['10028', '1003A'],
		['1003C', '1003D'],
		['1003F', '1004D'],
		['10050', '1005D'],

		// 2	http://codepoints.net/linear_b_ideograms
		['10080', '100FA'],

		// 3	http://codepoints.net/aegean_numbers
		['10107', '10133'],

		// 4	http://codepoints.net/ancient_greek_numbers
		['10140', '10178'],
		['1018A'],

		// 5	http://codepoints.net/ancient_symbols

		// 6	http://codepoints.net/phaistos_disc
		['101FD'],

		// 7	http://codepoints.net/lycian
		['10280', '1029C'],

		// 8	http://codepoints.net/carian
		['102A0', '102D0'],

		// 9	http://codepoints.net/old_italic
		['10300', '1031E'],
		['10320', '10323'],

		// 10	http://codepoints.net/gothic
		['10330', '1034A'],

		// 11	http://codepoints.net/ugaritic
		['10380', '1039D'],

		// 12	http://codepoints.net/old_persian
		['103A0', '103C3'],
		['103C8', '103CF'],
		['103D1', '103D5'],

		// 13	http://codepoints.net/deseret
		['10400', '1044F'],

		// 14	http://codepoints.net/shavian
		['10450', '1047F'],





		// 15	http://codepoints.net/osmanya
		['10480', '1049D'],
		['104A0', '104A9'],



		// 16	http://codepoints.net/cypriot_syllabary
		['10800', '10805'],
		['10808'],
		['1080A', '10835'],
		['10837', '10838'],
		['1083C'],
		['1083F'],

		// 17	http://codepoints.net/imperial_aramaic
		['10840', '10855'],
		['10857', '1085F'],

		// 18	http://codepoints.net/phoenician
		['10900', '1091B'],

		// 19	http://codepoints.net/lydian
		['10920', '10939'],

		// 20	http://codepoints.net/meroitic_hieroglyphs
		['10980', '1099F'],

		// 21	http://codepoints.net/meroitic_cursive
		['109A0', '109B7'],
		['109BE', '109BF'],

		// 22	http://codepoints.net/kharoshthi
		['10A00', '10A03'],
		['10A05', '10A06'],
		['10A0C', '10A13'],
		['10A15', '10A17'],
		['10A19', '10A33'],
		['10A38', '10A3A'],
		['10A3F', '10A47'],

		// 23	http://codepoints.net/old_south_arabian
		['10A60', '10A7E'],

		// 24	http://codepoints.net/avestan
		['10B00', '10B35'],

		// 25	http://codepoints.net/inscriptional_parthian
		['10B40', '10B55'],
		['10B58', '10B5F'],

		// 26	http://codepoints.net/inscriptional_pahlavi
		['10B60', '10B72'],
		['10B78', '10B7F'],

		// 27	http://codepoints.net/old_turkic
		['10C00', '10C48'],

		// 28	http://codepoints.net/rumi_numeral_symbols
		['10E60', '10E7E'],

		// 29	http://codepoints.net/brahmi
		['11000', '11046'],
		['11052', '1106F'],

		// 30	http://codepoints.net/kaithi
		['11080', '110BA'],

		// 31	http://codepoints.net/sora_sompeng
		['110D0', '110E8'],
		['110F0', '110F9'],

		// 32	http://codepoints.net/chakma
		['11100', '11134'],
		['11136', '1113F'],

		// 33	http://codepoints.net/sharada
		['11180', '111C4'],
		['111D0', '111D9'],

		// 34	http://codepoints.net/takri
		['11680', '116B7'],
		['116C0', '116C9'],

		// 35	http://codepoints.net/cuneiform
		['12000', '1236E'],

		// 36	http://codepoints.net/cuneiform_numbers_and_punctuation
		['12400', '12462'],

		// 37	http://codepoints.net/egyptian_hieroglyphs
		['13000', '1342E'],

		// 38	http://codepoints.net/bamum_supplement
		['16800', '16A38'],

		// 39	http://codepoints.net/miao
		['16F00', '16F44'],
		['16F50', '16F7E'],
		['16F8F', '16F9F'],

		// 40	http://codepoints.net/kana_supplement
		['1B000', '1B001'],

		// 41	http://codepoints.net/byzantine_musical_symbols

		// 42	http://codepoints.net/musical_symbols

		// 43	http://codepoints.net/ancient_greek_musical_notation

		// 44	http://codepoints.net/tai_xuan_jing_symbols

		// 45	http://codepoints.net/counting_rod_numerals
		['1D360', '1D371'],

		// 46	http://codepoints.net/mathematical_alphanumeric_symbols


		// 47	http://codepoints.net/arabic_mathematical_alphabetic_symbols

		// 48	http://codepoints.net/mahjong_tiles

		// 49	http://codepoints.net/domino_tiles

		// 50	http://codepoints.net/playing_cards

		// 51	http://codepoints.net/enclosed_alphanumeric_supplement
		['1F100', '1F10A'],

		// 52	http://codepoints.net/enclosed_ideographic_supplement

		// 53	http://codepoints.net/miscellaneous_symbols_and_pictographs

		// 54	http://codepoints.net/emoticons

		// 55	http://codepoints.net/transport_and_map_symbols

		// 56	http://codepoints.net/alchemical_symbols














		// 1	http://codepoints.net/cjk_unified_ideographs_extension_b
		['20000', '2A6D6'],

		// 2	http://codepoints.net/cjk_unified_ideographs_extension_c
		['2A700', '2B734'],

		// 3	http://codepoints.net/cjk_unified_ideographs_extension_d
		['2B740', '2B81D'],

		// 4	http://codepoints.net/cjk_compatibility_ideographs_supplement
		['2F800', '2FA10'],

		// 5	http://codepoints.net/tags






		// 1	http://codepoints.net/tags

		// 2	http://codepoints.net/variation_selectors_supplement

	];

	// 分割 a[] = ['组id', utf-32 代码 开始, utf-32 代码 结束 (可选)];			组 ID = 空 那就 没 + 单个字符串那样分割
	public $split = [
		['int+en', '0000', '007F'],
		['', '2150', '218F'],
		['', '2460', '24FF'],
		['', '3099', '309F'],
		['', '30A0'],
	];




	// 引用
	public $callback;



	public function __construct($query = '') {
		$this->query = mb_strtolower(is_array($query) ? implode(' ', $query) : (string) $query);
	}

	/**
	*	替换字符串
	*
	*	1 参数 字符串
	*
	*	返回值 字符串
	***/
	public function replace($str) {
		$str = is_array($str) ? implode(' ', $str) : $str;
		$replace = [];
		foreach ($this->replace as $v) {
			$replace[$v[0]][] = $v;
		}

		$k = '';
		$this->callback = &$k;
		foreach ($replace as $k => $v) {
			$pattern = '';
			foreach ($v as $vv) {
				$pattern .= '\x{'. $vv[1] .'}' . (empty($vv[2]) ? '' : '-\x{'. $vv[2] .'}');
			}
			$str = preg_replace_callback('/[' . $pattern . ']/u', [$this, 'callback'], $str);
		}
		return $str;
	}




	/**
	*	字符串分割
	*
	*	1 字符串
	*
	*	返回值 字符串
	**/
	public function split($a) {
		$split = [];
		foreach ($this->split as $v) {
			$split[$v[0]][] = $v;
		}

		$a = is_array($a) ? implode(' ', $a) : $a;
		foreach ($split as $k => $v) {
			$pattern = '';
			foreach ($v as $vv) {
				$pattern .= '\x{'. $vv[1] .'}' . (empty($vv[2]) ? '' : '-\x{'. $vv[2] .'}');
			}
			$a = preg_replace('/([' . $pattern . ']'. ($k ? '+' : '') . ')/u', ' $1 ', $a);
		}
		return array_unique(array_filter(array_map('trim', explode(' ', $a))));
	}

	/**
	*	匹配字符串
	*
	*	1 参数 字符串
	*
	*	返回值 字符串
	**/

	public function match($str, $pattern) {
		$x = '[';
		foreach ($this->match as $k => $v) {
			$x .= '\x{'. $v[0] .'}' . (empty($v[1]) ? '' : '-\x{'. $v[1] .'}');
		}
		$x .= ']';
		if (!preg_match_all('/'. str_replace(['\x', '/'], [$x, '\/'], $pattern) .'/u', is_array($str) ? implode(' ', $str) : $str, $matchs)) {
			return [];
		}
		return $matchs;
	}


	/**
	*	replace 的回调函数
	*
	*	回调函数
	*
	*	返回值 字符串
	**/
	public function callback($a) {
		if (is_int($this->callback)) {
			$a = mb_convert_encoding($a[0],'utf-32', 'auto');
			$a = unpack('H*', $a);
			$a = base_convert($a[1], 16, 10) - $this->callback;
			$a = base_convert($a, 10, 16);
		} else {
			$a = $this->callback;
		}
		$a = sprintf("%08d", $a);
		$str = '';
		for($i = 0; $i < 8; $i += 2) {
			$str .= pack('H2', substr($a, $i, 2));
		}
		return mb_convert_encoding($str, mb_internal_encoding(), 'utf-32');
	}



	public function get() {
		$query = $this->replace($this->query);
		if (!$query = $this->match($query, '(\-|)?(\x+)')) {
			return [];
		}
		$a = [];
		foreach ($query[0] as $k => $v) {
			$a[$query[1][$k]][] = $query[2][$k];
		}
		foreach ($a as &$q) {
			$q = $this->split($q);
		}
		return $a;
	}


	public function set() {
		$query = $this->replace($this->query);
		return ($query = $this->match($query, '(\x+)')) ? $this->split($query[1]) : [];
	}


	public function arr() {
		$query = $this->replace($this->query);
		return ($query = $this->match($query, '(\x+)')) ? $this->split($query[1]) : [];
	}
}
