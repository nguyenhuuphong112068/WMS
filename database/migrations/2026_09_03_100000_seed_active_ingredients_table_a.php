<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEED DỮ LIỆU GỐC "TÊN HOẠT CHẤT" - BẢNG A Phụ lục IV Nghị định 24/2026/NĐ-CP.
 * ---------------------------------------------------------------------------
 * Toàn bộ 271 dòng của Bảng A ("Danh mục hoá chất phải xây dựng Kế hoạch phòng ngừa,
 * ứng phó sự cố hoá chất"), bóc từ bản ký số của Nghị định 24/2026/NĐ-CP (Phụ lục IV,
 * trang 1-22). Mỗi dòng gồm: tên tiếng Việt, tên khoa học (danh pháp IUPAC), số CAS,
 * công thức hoá học và "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời
 * điểm (kg)".
 *
 * QUY TẮC:
 *   - Tất cả các dòng đặt is_table_a = 1, is_statutory = 1, app_status = 'approved'
 *     (ngưỡng lấy trực tiếp từ văn bản luật nên dùng để cảnh báo được ngay).
 *   - '---' ở số CAS / công thức của văn bản => lưu null.
 *   - Ngưỡng của văn bản viết kiểu Việt ("5.000" = 5000 kg); ở mảng ROWS ghi thẳng số kg.
 *   - Một số dòng có ngưỡng tách theo % khối lượng của hỗn hợp (STT 8 Amoni nitrat,
 *     STT 135 Kali nitrat): threshold_kg lấy mức của chất TINH KHIẾT / mức thấp nhất,
 *     các mức còn lại ghi ở cột note.
 *   - Bảng B (21 nhóm hỗn hợp phân loại theo GHS) KHÔNG nằm ở bảng này.
 *
 * up():   updateOrInsert theo 'name' (chạy lại không tạo bản trùng) + dọn các dòng
 *         luật định cũ do hệ thống seed nhưng không còn trong danh mục chuẩn và chưa
 *         bị tên hoá chất nào tham chiếu.
 * down(): chỉ gỡ đúng các dòng do file này seed (is_statutory = 1, created_by = 'Hệ thống').
 */
return new class extends Migration
{
    private const LEGAL_REF = 'Nghị định 24/2026/NĐ-CP - Phụ lục IV - Bảng A';
    private const DEFAULT_NOTE = 'Ngưỡng theo Bảng A Phụ lục IV Nghị định 24/2026/NĐ-CP.';

    /**
     * [STT, tên tiếng Việt, tên khoa học (IUPAC), số CAS|null, công thức|null, ngưỡng kg|null, ghi chú|null]
     */
    private const ROWS = [
        [1, 'Acrolein', 'Acrolein (2-Propenal)', '107-02-8', 'C3H4O', 5000, null],
        [2, 'Acrylonitril', 'Acrylonitrile', '107-13-1', 'C3H3N', 50000, null],
        [3, 'Acryloyl clorua', 'Acryloyl chloride (2-Propenoyl chloride)', '814-68-6', 'C3H3ClO', 5000, null],
        [4, 'Aldicarb', 'Aldicarb', '116-06-3', 'C7H14N2O2S', 5000, null],
        [5, 'Rượu alyl (2-Propen-1-ol)', 'Allyl alcohol (2-Propen-1-ol)', '107-18-6', 'C3H6O', 5000, null],
        [6, 'Alylamin (2-Propen-1-amin)', 'Allylamine (2-Propen-1-amine)', '107-11-9', 'C3H7N', 5000, null],
        [7, 'Amoniac khan', 'Ammonia (anhydrous)', '7664-41-7', 'NH3', 50000, null],
        [8, 'Amoni nitrat', 'Ammonium nitrate', '6484-52-2', 'NH4NO3', 10000,
            'Ngưỡng theo % khối lượng hỗn hợp: hỗn hợp <=70% => 5.000.000 kg; >70% và <=80% => 1.250.000 kg; >80% và <=98% => 350.000 kg; Amoni nitrat và hỗn hợp >=98% => 10.000 kg.'],
        [9, 'Anabasin (Pyridin,3-(2S)-2-piperidinyl)', 'Anabasine, (Pyridine,3-(2S)-2-piperidinyl-)', '494-52-0', 'C10H14N2', 50000, null],
        [10, 'Asen hydrua', 'Arsen trihydride (arsine)', '7784-42-1', 'AsH3', 200, null],
        [11, 'Axit asenic và/hoặc các muối asenat', 'Arsenic (V) acid and/or salts', null, 'H3AsO4', 1000, null],
        [12, 'Asen pentoxit', 'Arsenic pentoxide', '1303-28-2', 'As2O5', 1000, null],
        [13, 'Asen trioxit', 'Arsenic trioxide', '1327-53-3', 'As2O3', 100, null],
        [14, 'Asen triclorua', 'Arsenous trichloride', '7784-34-1', 'AsCl3', 50000, null],
        [15, 'Axit aseno và các muối asenit', 'Arsenious (III) acid and/or salts', null, 'HAsO2', 100, null],
        [16, 'Axetaldehit', 'Acetaldehyde', '75-07-0', 'C2H4O', 5000, null],
        [17, 'Axetylen', 'Acetylene', '74-86-2', 'C2H2', 5000, null],
        [18, 'Azinphos-etyl', 'Azinphos-ethyl', '2642-71-9', 'C12H16N3O3PS2', 5000, null],
        [19, 'Azinphos-metyl', 'Azinphos-methyl', '86-50-0', 'C10H12N3O3PS2', 50000, null],
        [20, 'Bari azit', 'Barium azide', '18810-58-7', 'Ba(N3)2', 10000, null],
        [21, 'Beryli (dạng bột và các hợp chất)', 'Beryllium (powders, compounds)', '7440-41-7', 'Be', 100, null],
        [22, 'Bis (2,4,6-trinitrophenyl) amin', 'Bis (2,4,6-trinitrophenyl) amine', '131-73-7', 'C12H5N7O12', 10000, null],
        [23, 'Bis (2-clo etyl) sunfua', 'Bis (2-chloroethyl) sulphide', '505-60-2', 'C4H8Cl2S', 5000, null],
        [24, 'Bis (2-dimetylaminoetyl) (metyl)amin', 'Bis (2- dimethyl aminoethyl) (methyl)amine', '3030-47-5', 'C9H23N3', 50000, null],
        [25, 'Bis (clo metyl) ete', 'Bis (chloromethyl) ether', '542-88-1', 'C2H4Cl2O', 50000, null],
        [26, '2,2-Bis(tert-butylperoxy) butan (>70%)', '2,2- Bis(tert-butylperoxy) butane (>70%)', '2167-23-9', 'C12H26O4', 10000, null],
        [27, '1,1-Bis(tert-butylperoxy) xyclohexan (>80%)', '1,1- Bis(tert-butylperoxy) xyclohexan (>80%)', '3006-86-8', 'C14H28O4', 10000, null],
        [28, 'Boron triclorua', 'Boron trichloride (Borane, trichloro-)', '10294-34-5', 'BCl3', 5000, null],
        [29, 'Boron triflorua', 'Boron trifluoride (Borane, trifluoro-)', '20654-88-0 / 7637-07-2', 'BF3', 5000, null],
        [30, 'Hỗn hợp boron triflorua và metyl ete (1:1)', 'Boron trifluoride compound with methyl ether (1:1) (Boron, trifluoro (oxybis (metane)-, T-4-)', '353-42-4', 'C2H6BF3O', 5000, null],
        [31, 'Brom', 'Bromine', '7726-95-6', 'Br2', 20000, null],
        [32, '1-Brom-3-cloropropan', '1-Bromo-3- chloropropane', '109-70-6', 'C3H6BrCl', 500, null],
        [33, 'Metyl bromua', 'Bromomethane (methyl bromide)', '74-83-9', 'CH3Br', 5000, null],
        [34, 'Brom triflo etylen', 'Bromotrifluorethylene (Ethene, bromotrifluoro-)', '598-73-2', 'C2BrF3', 10000, null],
        [35, '1,3-Butadien', '1,3-Butadiene', '106-99-0', 'C4H6', 10000, null],
        [36, 'Butan', 'Butane', '106-97-8', 'C4H10', 10000, null],
        [37, '1-Buten', '1-Butene', '106-98-9', 'C4H8', 10000, null],
        [38, '2-Buten', '2-Butene', '107-01-7 / 590-18-1 / 624-64-6', 'C4H8', 10000, null],
        [39, 'Buten', 'Butene', '25167-67-3', 'C4H8', 10000, null],
        [40, 'Tert-butyl acrylat', 'Tert-butyl acrylate', '1663-39-4', 'C7H12O2', 200000, null],
        [41, 'Tert-butyl peroxy isobutyrat (>80%)', 'Tert-butyl peroxy isobutyrate (>80%)', '109-13-7', 'C8H16O3', 5000, null],
        [42, 'Tert-butyl peroxyaxetat (>70%)', 'Tert-butyl peroxyacetate (>70%)', '107-71-1', 'C6H12O3', 10000, null],
        [43, 'Tert-butylperoxy isopropyl cacbonat (>80%)', 'Tert-butylperoxy isopropylcarbonate (>80%)', '2372-21-6', 'C8H16O4', 10000, null],
        [44, 'Cacbofuran', 'Carbofuran', '1563-66-2', 'C12H15NO3', 5000, null],
        [45, 'Cacbon disunfua', 'Carbon disulfide', '75-15-0', 'CS2', 10000, null],
        [46, 'Cacbon oxysunfua', 'Carbon oxysulfide (Carbon oxide sulfide (COS))', '463-58-1', 'COS', 10000, null],
        [47, 'Cacbon phenothion', 'Carbon phenothion', '786-19-6', 'C11H16ClO2PS3', 5000, null],
        [48, 'Cacbonyl clorua (phosgen)', 'Carbonyl dichloride (phosgene)', '75-44-5', 'CCl2O', 300, null],
        [49, 'Chì 2,4,6-trinitroresorcinoxit', 'Lead 2,4,6-trinitroresorcinoxide (lead styphnate)', '63918-97-8', 'C6HN3O8Pb', 50000, null],
        [50, 'Các ankyl chì', 'Lead alkyls', null, null, 5000, null],
        [51, 'Chì azit', 'Lead azide', '13424-46-9', 'PbN6', 10000, null],
        [52, '1-Clo propylene', '1-Chloropropylene (1-Propene, 1-chloro-)', '590-21-6', 'C3H5Cl', 10000, null],
        [53, 'Clo fenvinphos', 'Chlorfenvinphos', '470-90-6', 'C12H14Cl3O4P', 5000, null],
        [54, 'Clo', 'Chlorine', '7782-50-5', 'Cl2', 10000, null],
        [55, 'Clo dioxit', 'Chlorine dioxide (Chlorine oxide (ClO2))', '10049-04-4', 'ClO2', 5000, null],
        [56, 'Cloroform', 'Chloroform (methane, trichloro-)', '67-66-3', 'CHCl3', 5000, null],
        [57, 'Clormetyl metyl ete', 'Chloromethyl methyl ether', '107-30-2', 'C2H5ClO', 5000, null],
        [58, 'Isopropyl clorua', '2-chloropropane', '75-29-6', 'C3H7Cl', 10000, null],
        [59, '2-Clo propylene', '2-Chloropropylene (1-Propene, 2-chloro-)', '557-98-2', 'C3H5Cl', 10000, null],
        [60, 'Clo trinitro benzen', 'Chlorotrinitrobenzene', '88-88-0', 'C6H2ClN3O6', 5000, null],
        [61, 'Coban kim loại và các hợp chất oxit, carbonat, sulfua dạng bột', 'Cobalt metal, oxides, carbonates, sulphides, as powders', null, null, 5000, null],
        [62, 'Crimidin', 'Crimidine', '535-89-7', 'C7H10ClN3', 5000, null],
        [63, '2-Butenal', 'Crotonaldehyde (2-Butenal)', '4170-30-3 / 123-73-9 / 15798-64-8', 'C4H6O', 5000, null],
        [64, 'Demeton', 'Demeton', '8065-48-3', 'C16H38O6P2S4', 5000, null],
        [65, 'Dialifos', 'Dialifos', '10311-84-9', 'C14H17ClNO4PS2', 50000, null],
        [66, 'Diazo dinitro phenol', 'Diazodinitrophenol', '87-31-0', 'C6H2N4O5', 10000, null],
        [67, 'Dibenzyl peroxy dicacbonat (>90%)', 'Dibenzyl peroxy dicarbonate (>90%)', '2144-45-8', 'C16H14O6', 10000, null],
        [68, 'Diboran', 'Diborane', '19287-45-7', 'B2H6', 5000, null],
        [69, '1,2-Dibrom etan', '1,2-Dibromoethane (ethylene dibromide)', '106-93-4', 'C2H4Br2', 50000, null],
        [70, 'Diclo silan', 'Dichlorosilane (silane, dichloro-)', '4109-96-0', 'Cl2H2Si', 5000, null],
        [71, 'oo-Dietyl s-etylsunphinylmetyl photphothioat', 'oo-Diethyl s-ethylsulphinylmethyl phosphorothioate', '2588-05-8', 'C7H17O4PS2', 5000, null],
        [72, 'oo-Dietyl s-etyl sunphonylmetyl photphothioat', 'oo-Diethyl s-ethyl sulphonylmethyl phosphorothioate', '2588-06-9', 'C7H17O5PS2', 5000, null],
        [73, 'oo-Dietyl s-etyl thiometyl photphothioat', 'oo-Diethyl s-ethyl thiomethyl phosphorothioate', '2600-69-3', 'C7H17O3PS2', 5000, null],
        [74, 'oo-Dietyl s-iso propylthiometyl photphodithioat', 'oo-Diethyl s-iso propylthiomethyl phosphorodithioate', '78-52-4', 'C8H19O2PS3', 5000, null],
        [75, 'oo-Dietyl s-propyl thiometyl photphodithioat', 'oo-Diethyl s-propyl thiomethyl phosphorodithioate', '3309-68-0', 'C8H19O2PS3', 5000, null],
        [76, 'Dietylen glycol dinitrat', 'Diethylene glycol dinitrate', '693-21-0', 'C4H8N2O7', 10000, null],
        [77, 'Dietyl peroxy dicarbonat (>30%)', 'Dietyl peroxy dicarbonate (>30%)', '14666-78-5', 'C6H10O6', 10000, null],
        [78, '1,1 Diflo etan', 'Difluoroethane (Ethane, 1,1-difluoro-)', '75-37-6', 'C2H4F2', 10000, null],
        [79, '2,2-Dihydro peroxypropan (>30%)', '2,2 Dihydro peroxypropane (>30%)', '2614-76-8', 'C3H8O4', 10000, null],
        [80, 'Di-isobutyryl peroxit (>50%)', 'Di-isobutyryl peroxide (>50%)', '3437-84-1', 'C8H14O4', 10000, null],
        [81, 'Dimefox', 'Dimefox', '115-26-4', 'C4H12FN2OP', 5000, null],
        [82, 'Dimetyl amin', 'Dimethylamine (Methanamine, N-methyl-)', '124-40-3', 'C2H7N', 5000, null],
        [83, 'Dimetylcacbamoyl clorua', 'Dimethylcarbamoyl chloride', '79-44-7', 'C3H6ClNO', 50000, null],
        [84, 'Dimetyldiclo silan', 'Dimethyldichlorosilane (silane, dichlorodimethyl-)', '75-78-5', 'C2H6Cl2Si', 5000, null],
        [85, 'Dimetyl ete', 'Methyl ether (Methane, oxybis-)', '115-10-6', 'C2H6O', 10000, null],
        [86, 'Dimetyl nitrosamin', 'Dimethylnitrosamine', '62-75-9', 'C2H6N2O', 5000, null],
        [87, '2,2-Dimetyl propan', '2,2-Dimethylpropane (Propane, 2,2-dimethyl-)', '463-82-1', 'C5H12', 10000, null],
        [88, 'Axit dimetyl photphoramido xyanidic', 'Dimetylphosphoramid ocyanidic acid', '63917-41-9', 'C3H7N2P', 1000, null],
        [89, 'Di-n-propylperoxy dicacbonat (>80%)', 'Di-n-propylperoxy dicarbonate (>80%)', '16066-38-9', 'C8H14O6', 10000, null],
        [90, 'Diphacinon', 'Diphacinone', '82-66-6', 'C23H16O3', 5000, null],
        [91, 'Di-sec-butyl peroxydicacbonat (>80%)', 'Di-sec-butyl peroxydicarbonate (>80%)', '19910-65-7', 'C10H18O6', 10000, null],
        [92, 'Disulfoton', 'Disulfoton', '298-04-4', 'C8H19O2PS3', 5000, null],
        [93, 'Epiclohydrin', 'Epichlorohydrin (oxirane, (chloromethyl-)', '106-89-8', 'C3H5ClO', 5000, null],
        [94, 'Epn (Photphonothioic acid, P-phenyl-, O-ethyl O-(4-nitrophenyl) este)', 'Epn (Phosphonothioic acid, P-phenyl-, O-ethyl O-(4-nitrophenyl) ester)', '2104-64-5', 'C14H14NO4PS', 5000, null],
        [95, 'Etan', 'Ethane', '74-84-0', 'C2H6', 10000, null],
        [96, 'Ethion', 'Ethion', '563-12-2', 'C9H22O4P2S4', 50000, null],
        [97, 'Etyl amin', 'Ethylamine (Ethanamine)', '75-04-7', 'C2H7N', 5000, null],
        [98, 'Etyl axetylen', 'Ethyl acetylene (1-Butyne)', '107-00-6', 'C4H6', 10000, null],
        [99, 'Etyl clorua', 'Ethyl chloride (Ethane, chloro)', '75-00-3', 'C2H5Cl', 10000, null],
        [100, 'Etyl ete', "Ethyl ether (Ethane, 1,1'-oxybis-)", '60-29-7', 'C4H10O', 10000, null],
        [101, 'Etyl mercaptan', 'Ethyl mercaptan (Ethanethiol)', '75-08-1', 'C2H6S', 10000, null],
        [102, 'Etyl nitrat', 'Ethyl nitrate', '625-58-1', 'C2H5NO3', 50000, null],
        [103, 'Etyl nitro', 'Ethyl nitrite (Nitrous acid, ethyl ester)', '109-95-5', 'C2H5NO2', 10000, null],
        [104, 'Etylen glycol dinitrat', 'Ethylene glycol dinitrate', '628-96-6', 'C2H4N2O6', 10000, null],
        [105, 'Etylen oxit', 'Ethylene oxide', '75-21-8', 'C2H4O', 5000, null],
        [106, 'Etylen diamin', 'Ethylenediamine (1,2-Ethanediamine)', '107-15-3', 'C2H8N2', 5000, null],
        [107, 'Etylenimin', 'Ethyleneimine', '151-56-4', 'C2H5N', 10000, null],
        [108, '3-(2-Etylhexyloxy) propylamin', '3-(2-Ethylhexyloxy) propylamin', '5397-31-9', 'C11H25NO', 50000, null],
        [109, 'Flo', 'Fluorine', '7782-41-4', 'F2', 10000, null],
        [110, 'Axit flo axetic', 'Fluoroacetic acid', '144-49-0', 'C2H3FO2', 5000, null],
        [111, 'Fluenetil (2-floetyl 4-Biphenylaxetat)', 'Fluenetil', '4301-50-2', 'C16H15FO2', 5000, null],
        [112, 'Formaldehit (nồng độ >=90%)', 'Formaldehyde (Conc. >90%)', '50-00-0', 'CH2O', 5000, 'Văn bản in số CAS "50-00-00"; số CAS chuẩn của Formaldehyde là 50-00-0. Ngưỡng theo Bảng A Phụ lục IV NĐ 24/2026/NĐ-CP.'],
        [113, 'Furan', 'Furan', '110-00-9', 'C4H4O', 10000, null],
        [114, '1-Guanyl-4-nitrosaminoguanyl-1-tetrazen', '1-Guanyl-4-nitrosaminoguanyl-1-tetrazene', '109-27-3', 'C2H8N10O', 10000, null],
        [115, '1,2,3,7,8,9-Hexaclo dibenzo-p-dioxin', '1,2,3,7,8,9-Hexachlorodibenzo-p-dioxin', '19408-74-3', 'C12H2Cl6O2', 100, null],
        [116, '3,3,6,6,9,9-Hexametyl-1,2,4,5-tetroxacyclononat (>75%)', '3,3,6,6,9,9-Hexamethyl-1,2,4,5-tetroxacyclononate (>75%)', '22397-33-7', 'C12H22O4', 5000, null],
        [117, 'Hexametylphotphor oamit', 'Hexamethylphosphor oamide', '680-31-9', 'C6H18N3OP', 50000, null],
        [118, "2,2',4,4',6,6'-Hexanitro stilben", "2,2',4,4',6,6'-hexanitrostilbene", '20062-22-0', 'C14H6N6O12', 10000, null],
        [119, 'Hydrazin', 'Hydrazine', '302-01-2', 'H4N2', 5000, null],
        [120, 'Hydrazin nitrat', 'Hydrazine nitrate', '13464-97-6', 'H5N3O3', 50000, null],
        [121, 'Hydro', 'Hydrogen', '1333-74-0', 'H2', 5000, null],
        [122, 'Hydro clorua và axit clohydric', 'Hydrogen chloride and Chlohydric acid', '7647-01-0', 'HCl', 25000, null],
        [123, 'Hydro florua', 'Hydrogen fluoride', '7664-39-3', 'HF', 5000, null],
        [124, 'Hydro selenua', 'Hydrogen selenide', '7783-07-5', 'H2Se', 10000, null],
        [125, 'Hydro sunfua', 'Hydrogen sulphide', '7783-06-4', 'H2S', 5000, null],
        [126, 'Axit hydroxyanic', 'Hydrocyanic acid', '74-90-8', 'HCN', 5000, null],
        [127, '5-hydroxy naphthalen-1,4-dion', '5-Hydroxy-1,4-naphthalenedione', '481-39-0', 'C10H6O3', 10000, null],
        [128, 'Hydroxy axetonitril', 'Hydroxy acetonitrile (glycolonitrile)', '107-16-4', 'C2H3NO', 5000, null],
        [129, 'Isobenzan', 'Isobenzan', '297-78-9', 'C9H4Cl8O', 5000, null],
        [130, 'Isobutyronitril (2-metyl propan nitril)', '2-methyl-Propanenitrile', '78-82-0', 'C4H7N', 10000, null],
        [131, 'Isodrin', 'Isodrin', '465-73-6', 'C12H8Cl6', 1000, null],
        [132, 'Isopentan', '2-methyl-Butane', '78-78-4', 'C5H12', 5000, null],
        [133, 'Isopren', '2-methyl-1,3-butadiene', '78-79-5', 'C5H8', 10000, null],
        [134, 'Isopropyl cloformat', '1-methylethyl chlorocarbonate', '108-23-6', 'C4H7ClO2', 5000, null],
        [135, 'Kali nitrat', 'Potassium nitrate', '7757-79-1', 'KNO3', 1250000,
            'Ngưỡng theo dạng: dạng hạt => 5.000.000 kg; dạng tinh thể => 1.250.000 kg.'],
        [136, 'Các khí hoá lỏng đặc biệt dễ cháy (bao gồm cả LPG) và khí thiên nhiên', 'Liquefied extremely flammable gases (including LPG) and natural gas', null, null, 50000, null],
        [137, 'Lưu huỳnh diclorua', 'Sulfur dichloride', '10545-99-0', 'SCl2', 100, null],
        [138, 'Lưu huỳnh dioxit', 'Sulfur dioxide', '7446-09-5', 'SO2', 50000, null],
        [139, 'Lưu huỳnh tetraflorua', 'Sulfur tetrafloride (Sulfur fluoride)', '7783-60-0', 'SF4', 5000, null],
        [140, 'Lưu huỳnh trioxit', 'Sulfur trioxide', '7446-11-9', 'SO3', 15000, null],
        [141, 'Metan', 'Methane', '74-82-8', 'CH4', 10000, null],
        [142, 'Metanol', 'Methanol', '67-56-1', 'CH4O', 500000, null],
        [143, '3-Metyl 1-buten', '3-Methyl-1-butene', '563-45-1', 'C5H10', 5000, null],
        [144, 'Metyl acrylat', 'Methyl acrylate', '96-33-3', 'C4H6O2', 500000, null],
        [145, 'Metyl amin', 'Methylamine (Methanamine)', '74-89-5', 'CH5N', 5000, null],
        [146, 'Metyl clorua', 'Methyl chloride (Methane, chloro-)', '74-87-3', 'CH3Cl', 5000, null],
        [147, 'Metyl cloformat', 'Methyl chloroformate (Carbonochloridic acid, methylester)', '79-22-1', 'C2H3ClO2', 5000, null],
        [148, 'Metyl etyl keton peroxit (>60%)', 'Methyl ethyl ketone peroxide (>60%)', '1338-23-4', 'C8H18O6', 5000, null],
        [149, 'Metyl format', 'Methyl formate (Formic acid, methyl ester)', '107-31-3', 'C2H4O2', 5000, null],
        [150, 'Metyl hydrazin', 'Methyl hydrazine (Hydrazine, methyl-)', '60-34-4', 'CH6N2', 5000, null],
        [151, 'Metyl isobutyl keton peroxit (nồng độ >60%)', 'Methyl isobutyl ketone peroxide (>60%)', '37206-20-5', 'C12H26O4', 50000, null],
        [152, 'Metyl isoxyanat', 'Methyl isocyanate', '624-83-9', 'C2H3NO', 150, null],
        [153, 'Metyl mercaptan', 'Methyl mercaptan (Methanethiol)', '74-93-1', 'CH4S', 10000, null],
        [154, 'Metyl thioxyanat', 'Methyl thiocyanate (Thiocyanic acid, methyl ester)', '556-64-9', 'C2H3NS', 10000, null],
        [155, '2-Metyl 1-buten', '2-Methyl-1-butene', '563-46-2', 'C5H10', 10000, null],
        [156, 'Metacrylonitril', '2-methyl-2-Propenenitrile', '126-98-7', 'C4H5N', 10000, null],
        [157, '2-Metyl-3-buten nitril', '2-Methyl-3-butenenitrile', '16529-56-9', 'C5H7N', 500000, null],
        [158, '4,4-Metylen bis (2-clo anilin) và/hoặc muối của nó ở dạng bột', "4,4'-Methylenebis (2-chloroaniline) and/or salts, in powder form", '101-14-4', 'C13H12Cl2N2', 10, null],
        [159, 'Metyl isoxyanat (mục 159 Bảng A)', 'Methyl isocyanate (item 159)', '624-83-9', 'C2H3NO', 5000,
            'Bảng A liệt kê "Methyl isocyanate" hai lần (mục 152 ngưỡng 150 kg; mục 159 ngưỡng 5.000 kg). Giữ cả hai theo đúng văn bản.'],
        [160, 'n-Metyl-n,2,4,6-tetranitroanilin', 'n-Methyl-n,2,4,6-tetranitroaniline', '479-45-8', 'C7H5N5O8', 5000, null],
        [161, '2-Metyl 1-propen', '2-Methylpropene (1-Propene, 2-methyl-)', '115-11-7', 'C4H8', 10000, null],
        [162, '3-Metylpyridin', '3-Methylpyridine', '108-99-6', 'C6H7N', 500, null],
        [163, 'Metyl triclo silan', 'Methyltrichlorosilane (Silane, trichloromethyl-)', '75-79-6', 'CH3Cl3Si', 5000, null],
        [164, 'Mevinphos', 'Mevinphos', '7786-34-7', 'C7H13O6P', 5000, null],
        [165, 'Natri clorat', 'Sodium chlorate', '7775-09-9', 'NaClO3', 50000, null],
        [166, 'Natri picramat', 'Sodium picramate', '831-52-7', 'C6H4N3NaO5', 10000, null],
        [167, 'Natri selenit', 'Sodium selenite', '10102-18-8', 'Na2SeO3', 50000, null],
        [168, 'Hỗn hợp chứa natri hypoclorit', 'Mixtures of sodium hypochlorite', null, null, 200000, null],
        [169, 'Niken và các hợp chất chứa Ni dạng bột có thể phát tán trong không khí (oxit, cacbonat, sunfua)', 'Nickel compounds in inhalable powder form (oxides, sulphides, carbonate)', null, null, 1000, null],
        [170, 'Niken tetracacbonyl', 'Nickel tetracarbonyl', '13463-39-3', 'C4NiO4', 5000, null],
        [171, 'Axit nitric (nồng độ >=80%)', 'Nitric acid (conc 80% or greater)', '7697-37-2', 'HNO3', 5000, null],
        [172, 'Nitơ glyxerin', 'Nitroglycerin', '55-63-0', 'C3H5N3O9', 5000, null],
        [173, 'Nitơ monoxit', 'Nitric oxide (Nitrogen oxide (NO))', '10102-43-9', 'NO', 50000, null],
        [174, 'Nitơ oxit', 'Nitrogen oxides', '11104-93-1', 'NOx', 50000, null],
        [175, 'Nitơ xenlulo (hàm lượng >12,6% nitrogen)', 'Nitrocellulose (containing >12,6% of nitrogen)', '9004-70-0', null, 10000, null],
        [176, 'Oleum (hỗn hợp axit sunfuric với lưu huỳnh trioxit)', 'Oleum (Fuming Sulfuric acid) (Sulfuric acid, mixture with sulfur trioxide)', '8014-95-7', 'H2SO4*nSO3', 5000, null],
        [177, 'Oxy', 'Oxygen', '7782-44-7', 'O2', 200000, null],
        [178, 'Oxydisunfoton', 'Oxydisulfoton', '2497-07-6', 'C8H19O3PS3', 5000, null],
        [179, 'Oxy diflorua', 'Oxygen difloride', '7783-41-7', 'F2O', 5000, null],
        [180, 'Paraoxon (dietyl 4-nitrophenyl photphat)', 'Paraoxon (diethyl 4-nitrophenylphosphate)', '311-45-5', 'C10H14NO6P', 10000, null],
        [181, 'Parathion', 'Parathion', '56-38-2', 'C10H14NO5PS', 5000, null],
        [182, 'Parathion-metyl', 'Parathion-methyl', '298-00-0', 'C10H14NO5PS', 50000,
            'Công thức ghi theo văn bản (C10H14NO5PS - trùng công thức Parathion).'],
        [183, 'Pensunfothion', 'Pensulfothion', '115-90-2', 'C11H17O4PS2', 5000, null],
        [184, 'Pentaboran', 'Pentaborane', '19624-22-7', 'B5H9', 5000, null],
        [185, '1,3-Pentadien', '1,3-Pentadiene', '504-60-9', 'C5H8', 10000, null],
        [186, 'Pentaerythritol tetranitrat', 'Pentaerythritol tetranitrate', '78-11-5', 'C5H8N4O12', 10000, null],
        [187, 'Pentan', 'Pentane', '109-66-0', 'C5H12', 5000, null],
        [188, '1-Penten', '1-Pentene', '109-67-1', 'C5H10', 5000, null],
        [189, '(E)-2-Penten', '2-Pentene, (E)-', '646-04-8', 'C5H10', 5000, null],
        [190, '(Z)-2-Penten', '2-Pentene, (Z)-', '627-20-3', 'C5H10', 5000, null],
        [191, 'Axit peraxetic (>60%)', 'Peracetic acid (>60%)', '79-21-0', 'C2H4O3', 5000, null],
        [192, 'Perclometyl mercaptan', 'Perchloromethyl mercaptan (Methanesulfenyl chloride, trichloro-)', '594-42-3', 'CCl4S', 5000, null],
        [193, 'Photpho vàng', 'Phosphorus (White, yellow)', '7723-14-0', 'P4', 1000, null],
        [194, 'Phorat', 'Phorate', '298-02-2', 'C7H17O2PS3', 5000, null],
        [195, 'Phosacetim', 'Phosacetim', '4104-14-7', 'C14H13Cl2N2O2PS', 5000, null],
        [196, 'Phosphamidon', 'Phosphamidon', '13171-21-6', 'C10H19ClNO5P', 50000, null],
        [197, 'Photpho oxyclorua', 'Phosphorus oxychloride (Phosphoryl chloride)', '10025-87-3', 'POCl3', 5000, null],
        [198, 'Photpho triclorua', 'Phosphorus trichloride (Phosphorous trichloride)', '7719-12-2', 'PCl3', 5000, null],
        [199, 'Photpho trihydrua (photphin)', 'Phosphorus trihydride (phosphine)', '7803-51-2', 'PH3', 200, null],
        [200, 'Piperidin', 'Piperidine', '110-89-4', 'C5H11N', 50000, null],
        [201, 'Các Polyclo dibenzo furan và Polyclodibenzo dioxin (bao gồm TCDD)', 'Polychlorodibenzo-furans and Polychlorodibenzo-dioxins (including TCDD)', '33857-26-0', 'C12H6Cl2O2', 1, null],
        [202, 'Propylen imin', '2-methyl-Aziridine', '75-55-8', 'C3H7N', 10000, null],
        [203, 'Promurit (1-(3,4-diclophenyl)-3-triazenethiocacboxamit)', 'Promurit (1-(3,4-dichlorophenyl)-3-triazene thiocarboxamide)', '5836-73-7', 'C7H6Cl2N4S', 5000, null],
        [204, 'Propadien', '1,2-Propadiene', '463-49-0', 'C3H4', 10000, null],
        [205, 'Isopropylamin', '2-Propanamine', '75-31-0', 'C3H9N', 10000, null],
        [206, 'Propan', 'Propane', '74-98-6', 'C3H8', 10000, null],
        [207, '1-Propen-2-clo-1,3-diol diaxetat', '1-propen-2-chloro-1,3-diol-diacetate', '10118-77-6', 'C7H9ClO4', 10, null],
        [208, 'Propylen', '1-Propene', '115-07-1', 'C3H6', 10000, null],
        [209, 'Propionitril', 'Propionitrile (Propanenitrile)', '107-12-0', 'C3H5N', 5000, null],
        [210, 'Propyl cloformat', 'Propyl chloroformate (Carbonochloridic acid, propylester)', '109-61-5', 'C4H7ClO2', 5000, null],
        [211, 'Propylamin', 'Propylamine', '107-10-8', 'C3H9N', 500000, null],
        [212, 'Propylen oxit', 'Propylen oxide', '75-56-9', 'C3H6O', 5000, null],
        [213, 'Propin', '1-Propyne', '74-99-7', 'C3H4', 10000, null],
        [214, 'Pyrazoxon', 'Pyrazoxon', '108-34-9', 'C8H15N2O4P', 5000, null],
        [215, 'Sắt pentacacbonyl', 'Iron, pentacacbonyl-(Iron carbonyl (Fe (CO)5), (TB-5-11)-)', '13463-40-6', 'C5FeO5', 5000, null],
        [216, 'Selen hexaflorua', 'Selenium hexafloride', '7783-79-1', 'SeF6', 5000, null],
        [217, 'Silan', 'Silane', '7803-62-5', 'SiH4', 10000, null],
        [218, 'Stibin (antimon hydril)', 'Stibine (antimony hydril)', '7803-52-3', 'SbH3', 10000, null],
        [219, 'Sunfotepp', 'Sulfotepp', '3689-24-5', 'C8H20O5P2S2', 5000, null],
        [220, 'Tepp - tetraetyl pyrophotphat', 'T.E.P.P - (Tetraethyl pyrophosphate)', '107-49-3', 'C8H20O7P2', 5000, null],
        [221, 'Telu hexaflorua', 'Tellurium hexafloride', '7783-80-4', 'TeF6', 50000, null],
        [222, 'Tert-butylperoxy maleat (>80%)', 'Tert-butylperoxy maleate (>80%)', '1931-62-0', 'C8H12O5', 10000, null],
        [223, 'Tert-butylperoxy pivalat (>77%)', 'Tert-butylperoxy pivalate (>77%)', '927-07-1', 'C9H18O3', 10000, null],
        [224, '2,3,7,8-Tetraclo dibenzo-p-dioxin', '2,3,7,8-tetrachlorodibenzo-p-dioxin', '1746-01-6', 'C12H4Cl4O2', 5000, null],
        [225, 'Tetraflo etylen', 'Tetrafluoroethylene (Ethene, tetrafluoro-)', '116-14-3', 'C2F4', 10000, null],
        [226, 'Tetrahydro-3,5-dimetyl-1,3,5,-thiadiazin-2-thion (Dazomet)', 'Tetrahydro-3,5-dimethyl-1,3,5,-thiadiazine-2-thione (Dazomet)', '533-74-4', 'C5H10N2S2', 100000, null],
        [227, 'Tetrametylen disunphotetramin', 'Tetramethylened isulp hotetramine', '80-12-6', 'C4H8N4O4S12', 5000, null],
        [228, 'Tetrametyl silan', 'Tetramethylsilane (Silane, tetramethyl-)', '75-76-3', 'C4H12Si', 5000, null],
        [229, 'Tetranitro metan', 'Tetranitromethane (Methane, tetranitro-)', '509-14-8', 'CN4O8', 5000, null],
        [230, 'Thionazin', 'Thionazin', '297-97-2', 'C8H13N2O3PS', 5000, null],
        [231, 'Thủy ngân và các hợp chất của thủy ngân', 'Mercury and Mercury compounds', null, null, 1, null],
        [232, 'Tirpate (2,4-Dimetyl-2-formyl-1,3-dithiolan oxim metylcacbamat)', 'Tirpate (2,4-dimethyl-1,3-dithiolane-2-carbo xaldehydeo-methyl carbamoyloxime)', '26419-73-8', 'C8H14N2O2S2', 100, null],
        [233, 'Titan tetraclorua', 'Titanium tetrachloride (Titanium chloride (TiCl4) (T-4)-)', '7550-45-0', 'TiCl4', 5000, null],
        [234, '2,4-Toluen diisoxyanat', '2,4-Toluene di-isocyanate', '584-84-9', 'C9H6N2O2', 10000, null],
        [235, '2,6-Toluen di-isoxyanat', '2,6-Toluene di-isocyanate', '91-08-7', 'C9H6N2O2', 10000, null],
        [236, 'Toluen di-isoxyanat', 'Toluene di-isocyanate', '26471-62-5', 'C9H6N2O2', 10000, null],
        [237, '1,3,5-Triamino-2,4,6-trinitro benzen', '1,3,5-Triamino-2,4,6-trinitrobenzene', '3058-38-6', 'C6H6N6O6', 10000, null],
        [238, 'Triclo silan', 'Trichlorosilane (Silane, trichloro-)', '10025-78-2', 'SiHCl3', 5000, null],
        [239, 'Trietylenmelamin', 'Triethylenemelamine', '51-18-3', 'C9H12N6', 100, null],
        [240, 'Triflocloetylen', 'Trifluorochloroethylene (Ethene, chlorotrifluoro-)', '79-38-9', 'C2ClF3', 10000, null],
        [241, 'Trimetylamin', 'Trimethylamine', '75-50-3', 'C3H9N', 5000, null],
        [242, 'Trimetylclosilan', 'Trimethylchlorosilane (Silane, chlorotrimethyl-)', '75-77-4', 'C3H9ClSi', 5000, null],
        [243, 'Trinitro anilin', 'Trinitroaniline', '26952-42-1', 'C6H4N4O6', 50000, null],
        [244, '2,4,6-Trinitroanisol', '2,4,6-trinitroanisole', '606-35-9', 'C7H5N3O7', 10000, null],
        [245, '1,3,5-Trinitro benzen', 'Trinitrobenzene', '99-35-4', 'C6H3N3O6', 5000, null],
        [246, 'Axit trinitrobenzoic', 'Trinitrobenzoic acid', '129-66-8', 'C7H3N3O8', 10000, null],
        [247, 'Trinitro cresol', 'Trinitrocresol', '602-99-3', 'C7H5N3O7', 50000, null],
        [248, '2,4,6-Trinitrophenetol', '2,4,6-trinitrophenetole', '4732-14-3', 'C8H7N3O7', 10000, null],
        [249, '2,4,6-Trinitrophenol', '2,4,6-Trinitrophenol (picric acid)', '88-89-1', 'C6H3N3O7', 10000, null],
        [250, '2,4,6-Trinitroresorcinol', '2,4,6-Trinitroresorcinol (styphnic acid)', '82-71-3', 'C6H3N3O8', 10000, null],
        [251, '2,4,6-trinitrotoluen', '2,4,6-trinitrotoluene', '118-96-7', 'C7H5N3O6', 10000, null],
        [252, 'Vinyl axetat', 'Vinyl acetate monomer (Acetic acid ethenyl ester)', '108-05-4', 'C4H6O2', 10000, null],
        [253, 'Vinyl axetylen', 'Vinyl acetylene (1-Buten-3-yne)', '689-97-4', 'C4H4', 10000, null],
        [254, 'Vinyl clorua', 'Vinyl chloride (Ethene, chloro)', '75-01-4', 'C2H3Cl', 10000, null],
        [255, 'Vinyl etyl ete', 'Vinyl ethyl ether (Ethene, ethoxy-)', '109-92-2', 'C4H8O', 10000, null],
        [256, 'Vinyl florua', 'Vinyl fluoride (Ethene, fluoro)', '75-02-5', 'C2H3F', 10000, null],
        [257, 'Vinyl metyl ete', 'Vinyl methyl ether (Ethene, methoxy-)', '107-25-5', 'C3H6O', 10000, null],
        [258, 'Vinyliden clorua', 'Vinylidene chloride (Ethene, 1,1-dichloro-)', '75-35-4', 'C2H2Cl2', 10000, null],
        [259, 'Vinyliden florua', 'Vinylidene fluoride (Ethene, 1,1-difluoro-)', '75-38-7', 'C2H2F2', 10000, null],
        [260, 'Warfarin ((RS)-4-hydroxy-3-(3-oxo-1-phenylbutyl)-2H-chromen-2-on)', 'Warfarin ((RS)-4-hydroxy-3-(3-oxo-1-phenylbutyl)-2H-chromen-2-one)', '81-81-2', 'C19H16O4', 5000, null],
        [261, 'Xyanogen (Etandinitril)', 'Cyanogen (Ethanedinitrile)', '460-19-5', 'C2N2', 10000, null],
        [262, 'Xyanogen clorua', 'Cyanogen chloride', '506-77-4', 'CClN', 5000, null],
        [263, '2-xyano-2-propanol', '2-cyanopropan-2-ol (acetone cyanohydrin)', '75-86-5', 'C4H7NO', 5000, null],
        [264, 'Xyanthoat', 'Cyathoate', '3734-95-0', 'C10H19N2O4PS', 5000, null],
        [265, 'Các hợp chất xyanua', 'Cyanide compounds', null, null, 5000, null],
        [266, 'Xycloheximit', 'Cycloheximide', '66-81-9', 'C15H23NO4', 5000, null],
        [267, 'Xyclohexan amin', 'Cyclohexylamine (Cyclohexanamine)', '108-91-8', 'C6H13N', 5000, null],
        [268, 'Xyclopropan', 'Cyclopropane', '75-19-4', 'C3H6', 10000, null],
        [269, 'Xyclotetrametylen tetra nitramin', 'Cyclotetramethylenet etranitramine', '2691-41-0', 'C4H8N8O8', 10000, null],
        [270, 'Xyclotrimetylen trinitramin', 'Cyclotrimethylene trinitramine', '121-82-4', 'C3H6N6O6', 10000, null],
        [271, 'Nhóm chất gây ung thư (Bảng A mục 271) và hỗn hợp chứa các chất này trên 5% khối lượng', 'Carcinogens listed in item 271 of Table A, or mixtures containing them above 5% by weight', null, null, 500,
            'Gồm: 4-Aminobiphenyl và/hoặc muối của nó, Benzotriclorid, Benzidin và/hoặc các muối, Bis (clorometyl) ete, Clometyl metyl ete, 1,2-Dibrometan, Dietyl sunphat, Dimetyl sunphat, Dimetylcacbamoyl clorit, 1,2-Dibrom-3-clo propan, 1,2-Dimetylhydrazin, Dimetylnitrosamin, Hexametylphotphoric triamit, Hydrazin, 2-Naphtylamin và/hoặc muối của 4-Nitrodiphenyl và 1,3-Propanesulton. Ngưỡng áp dụng khi nồng độ chất gây ung thư > 5% khối lượng.'],
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('active_ingredients')) {
            return;
        }

        $now = now();

        // Mã A00001 tăng dần, bỏ qua mã đã tồn tại (chạy lại migration không cấp trùng)
        $maxNumber = DB::table('active_ingredients')
            ->where('code', 'like', 'A%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, 1))
            ->max() ?? 0;

        $canonicalNames = [];

        foreach (self::ROWS as $row) {
            [, $name, $nameEn, $cas, $formula, $thresholdKg, $note] = $row;

            $canonicalNames[] = $name;

            $payload = [
                'name_en' => $nameEn,
                'cas_no' => $cas,
                // Lưu công thức với chỉ số dưới Unicode (H₂SO₄) như phần còn lại của module
                'chemical_formula' => $formula === null ? null : \App\Support\ChemicalFormula::toSubscript($formula),
                'is_table_a' => 1,
                'threshold_kg' => $thresholdKg,
                'legal_ref' => self::LEGAL_REF,
                'is_statutory' => 1,
                'note' => $note ?? self::DEFAULT_NOTE,
                'app_status' => 'approved',
                'status_id' => 1,
                'approved_by' => 'Hệ thống',
                'approved_at' => $now,
                'updated_by' => 'Hệ thống',
                'updated_at' => $now,
            ];

            $existing = DB::table('active_ingredients')->where('name', $name)->first();

            if ($existing) {
                DB::table('active_ingredients')->where('id', $existing->id)->update($payload);
                continue;
            }

            $payload['name'] = $name;
            $payload['code'] = 'A' . str_pad((string) (++$maxNumber), 5, '0', STR_PAD_LEFT);
            $payload['created_by'] = 'Hệ thống';
            $payload['created_at'] = $now;

            DB::table('active_ingredients')->insert($payload);
        }

        // Dọn các dòng luật định cũ (do hệ thống seed ở phiên bản trước của file này)
        // không còn nằm trong danh mục chuẩn và CHƯA bị tên hoá chất nào tham chiếu.
        $linkedIds = DB::getSchemaBuilder()->hasTable('chem_name_active_ingredient')
            ? DB::table('chem_name_active_ingredient')->distinct()->pluck('active_ingredients_id')->all()
            : [];

        DB::table('active_ingredients')
            ->where('is_statutory', 1)
            ->where('created_by', 'Hệ thống')
            ->where('legal_ref', self::LEGAL_REF)
            ->whereNotIn('name', $canonicalNames)
            ->when($linkedIds, fn ($q) => $q->whereNotIn('id', $linkedIds))
            ->delete();
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('active_ingredients')) {
            return;
        }

        DB::table('active_ingredients')
            ->where('is_statutory', 1)
            ->where('created_by', 'Hệ thống')
            ->whereIn('name', array_map(fn ($row) => $row[1], self::ROWS))
            ->delete();
    }
};
