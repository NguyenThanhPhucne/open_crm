#!/usr/bin/env python3
# -*- coding: utf-8 -*-

translations = {
    "web/modules/custom/crm_activity_log/templates/activity-log-tab.html.twig": [
        ("Lịch sử tương tác với ", "Activity History with "),
        ("Tất cả hoạt động, cuộc gọi, và lịch hẹn", "All activities, calls, and meetings"),
        ("Chưa có hoạt động nào", "No activities yet"),
        ("Bắt đầu ghi nhận cuộc gọi hoặc đặt lịch hẹn với khách hàng này.", "Start by logging a call or scheduling a meeting with this contact."),
        ("Log cuộc gọi đầu tiên", "Log your first call"),
        ("<strong>Kết quả:</strong>", "<strong>Outcome:</strong>"),
        (">Sửa<", ">Edit<"),
    ],
    "web/modules/custom/crm_activity_log/templates/schedule-meeting-form.html.twig": [
        ("Đặt lịch hẹn với ", "Schedule Meeting with "),
        ("Tiêu đề cuộc họp", "Meeting Title"),
        ("Ví dụ: Meeting demo sản phẩm", "e.g. Product Demo Meeting"),
        (">Ngày<", ">Date<"),
        (">Giờ<", ">Time<"),
        ("Nội dung/Chương trình", "Agenda"),
        ("Nội dung cuộc họp, chủ đề cần thảo luận, tài liệu cần chuẩn bị...", "Meeting agenda, topics to discuss, materials to prepare..."),
        ("Deal liên quan (nếu có)", "Related Deal (if any)"),
        ("-- Không có deal --", "-- No deal --"),
        ("Lưu ý:", "Note:"),
        ("Sau khi đặt lịch, bạn sẽ thấy cuộc họp này trong calendar và sẽ nhận thông báo nhắc nhở.", "Once scheduled, this meeting will appear in your calendar and you will receive a reminder notification."),
        (">Hủy<", ">Cancel<"),
        (">Đặt lịch\n        </button>", ">Schedule\n        </button>"),
    ],
    "web/modules/custom/crm_activity_log/templates/activity-quick-actions.html.twig": [
        ("Ghi nhận cuộc gọi nhanh", "Log a quick call"),
        ("Đặt lịch hẹn", "Schedule a meeting"),
        ("Xem lịch sử tương tác", "View activity history"),
    ],
    "web/modules/custom/crm_quickadd/templates/quickadd-contact-form.html.twig": [
        ("Thêm khách hàng nhanh", "Quick Add Contact"),
        ("Tên khách hàng", "Full Name"),
        ("Nguyễn Văn A", "John Smith"),
        ("Số điện thoại", "Phone Number"),
        ("Chức vụ", "Job Title"),
        ("Phân loại", "Customer Type"),
        ("Nguồn KH", "Lead Source"),
        (">Công ty<", ">Company<"),
        ("Tên công ty mới", "New Company Name"),
        ("Công ty ABC", "ABC Company"),
        ("Lưu khách hàng", "Save Contact"),
        (">Hủy<", ">Cancel<"),
    ],
    "web/modules/custom/crm_quickadd/templates/quickadd-deal-form.html.twig": [
        ("Thêm cơ hội nhanh", "Quick Add Deal"),
        ("Tên cơ hội", "Deal Name"),
        ("Deal với Công ty ABC", "Deal with ABC Company"),
        ("Giá trị (VNĐ)", "Value"),
        (">Giai đoạn<", ">Stage<"),
        (">Khách hàng<", ">Contact<"),
        ("Ngày dự kiến chốt", "Expected Close Date"),
        ("Lưu cơ hội", "Save Deal"),
        (">Hủy<", ">Cancel<"),
    ],
    "web/modules/custom/crm_quickadd/templates/quickadd-organization-form.html.twig": [
        ("Thêm tổ chức nhanh", "Quick Add Organization"),
        ("Tên công ty", "Company Name"),
        ("Công ty TNHH ABC", "ABC Ltd."),
        ("Ngành nghề", "Industry"),
        (">Địa chỉ<", ">Address<"),
        ("123 Đường ABC, Quận 1, TP.HCM", "123 Main St, City, Country"),
        ("Lưu tổ chức", "Save Organization"),
        (">Hủy<", ">Cancel<"),
    ],
    "web/modules/custom/crm_activity_log/src/Controller/ActivityLogController.php": [
        ("'Vui lòng chọn kết quả cuộc gọi.'", "'Please select a call outcome.'"),
        ("'Đã ghi nhận cuộc gọi thành công.'", "'Call logged successfully.'"),
        ("'Có lỗi xảy ra. Vui lòng thử lại.'", "'An error occurred. Please try again.'"),
        ("'Vui lòng nhập đầy đủ Tiêu đề và Thời gian.'", "'Please fill in the meeting title and time.'"),
        ("'Đã đặt lịch hẹn thành công.'", "'Meeting scheduled successfully.'"),
    ],
    "web/modules/custom/crm_activity_log/src/Controller/ActivityApiController.php": [
        (" đ'", " VND'"),
        (' đ"', ' VND"'),
    ],
    "web/modules/custom/crm_quickadd/src/Controller/QuickAddController.php": [
        ("'-- Chọn công ty --'", "'-- Select company --'"),
        ("'+ Tạo công ty mới'", "'+ Create new company'"),
        ("'-- Chọn loại khách hàng --'", "'-- Select customer type --'"),
        ("'-- Chọn nguồn KH --'", "'-- Select lead source --'"),
        ("'Vui lòng nhập đầy đủ Tên và Số điện thoại.'", "'Please enter name and phone number.'"),
        ("'Số điện thoại này đã tồn tại trong hệ thống.'", "'This phone number already exists in the system.'"),
        ("'Đã tạo khách hàng thành công: '", "'Contact created successfully: '"),
        ("'Có lỗi xảy ra. Vui lòng thử lại.'", "'An error occurred. Please try again.'"),
        ("'-- Chọn khách hàng --'", "'-- Select contact --'"),
        ("'Vui lòng nhập đầy đủ Tên cơ hội và Giá trị.'", "'Please enter deal name and value.'"),
        ("'Đã tạo cơ hội thành công: '", "'Deal created successfully: '"),
        ("'-- Chọn ngành nghề --'", "'-- Select industry --'"),
        ("'Vui lòng nhập tên công ty.'", "'Please enter the company name.'"),
        ("'Đã tạo tổ chức thành công: '", "'Organization created successfully: '"),
        ("'Số điện thoại đã tồn tại'", "'Phone number already exists'"),
        ("'Email đã tồn tại'", "'Email already exists'"),
    ],
    "web/modules/custom/crm_kanban/src/Controller/KanbanController.php": [
        ("<h2>Chốt Deal Thành Công</h2>", "<h2>Close Deal</h2>"),
        ("Vui lòng nhập đầy đủ thông tin để hoàn tất việc chốt deal. Email thông báo sẽ được gửi đến quản lý.", "Please fill in all required information to close the deal. A notification email will be sent to the manager."),
        ("Ngày chốt deal <span class=\"required\">*</span>", "Close Date <span class=\"required\">*</span>"),
        ("Hợp đồng đính kèm <span style=\"color: #94a3b8; font-size: 13px;\">(tùy chọn)</span>", "Attached Contract <span style=\"color: #94a3b8; font-size: 13px;\">(optional)</span>"),
        ("Click để chọn file hợp đồng (không bắt buộc)", "Click to select contract file (optional)"),
        ("PDF, DOC, DOCX (tối đa 10MB)", "PDF, DOC, DOCX (max 10MB)"),
        (">Hủy\n        </button>", ">Cancel\n        </button>"),
        ("Xác nhận chốt deal\n        </button>", "Confirm Close Deal\n        </button>"),
        ("textContent = 'Vui lòng chọn ngày chốt deal'", "textContent = 'Please select the close date'"),
        ("textContent = 'File vượt quá 10MB. Vui lòng chọn file nhỏ hơn.'", "textContent = 'File exceeds 10MB. Please choose a smaller file.'"),
        ("✅ Đã chốt deal thành công!", "✅ Deal closed successfully!"),
        ("result.message || 'Có lỗi xảy ra. Vui lòng thử lại.'", "result.message || 'An error occurred. Please try again.'"),
        ("textContent = 'Có lỗi xảy ra. Vui lòng thử lại.'", "textContent = 'An error occurred. Please try again.'"),
    ],
    "web/modules/custom/crm_teams/src/Controller/TeamsManagementController.php": [
        ("Quản lý team assignments và phân quyền truy cập CRM data", "Manage team assignments and CRM data access permissions"),
    ],
    "web/modules/custom/crm_import_export/src/Service/DataValidationService.php": [
        ("'Email không được để trống'", "'Email is required.'"),
        ("'Email không đúng định dạng'", "'Invalid email format.'"),
        ("'Không chấp nhận email tạm thời'", "'Disposable email addresses are not allowed.'"),
        ("'Số điện thoại không được để trống'", "'Phone number is required.'"),
        ("'Số điện thoại không đúng định dạng (VD: 0912345678)'", "'Invalid phone number format (e.g. 0912345678).'"),
        ("'Giá trị deal không được để trống'", "'Deal value is required.'"),
        ("'Giá trị deal không được âm'", "'Deal value cannot be negative.'"),
        ("'Giá trị deal phải là số'", "'Deal value must be a number.'"),
        ("'Giá trị deal quá lớn'", "'Deal value is too large.'"),
        ("'%s không được để trống'", "'%s is required.'"),
        ("'Ngày không được để trống'", "'Date is required.'"),
        ("'Ngày không đúng định dạng (%s)'", "'Invalid date format (%s).'"),
        ("'Vui lòng chọn giá trị'", "'Please select a value.'"),
        ("'Giá trị không hợp lệ'", "'Invalid value.'"),
        ("'Vui lòng chọn'", "'Please select.'"),
        ("'Tham chiếu không hợp lệ'", "'Invalid reference.'"),
        ("'Tên khách hàng'", "'Contact Name'"),
        ("'Số điện thoại đã tồn tại (Contact ID: %d)'", "'Phone number already exists (Contact ID: %d).'"),
        ("'Email đã tồn tại (Contact ID: %d)'", "'Email already exists (Contact ID: %d).'"),
        ("'Tên deal'", "'Deal Name'"),
        ("'Tiêu đề hoạt động'", "'Activity Title'"),
        ("'Loại hoạt động'", "'Activity Type'"),
        ("'Người phụ trách không được để trống'", "'Assigned user is required.'"),
        ("'Hoạt động phải liên kết với Khách hàng hoặc Deal'", "'Activity must be linked to a Contact or Deal.'"),
        ("'Mức độ ưu tiên không hợp lệ. Chọn: %s'", "'Invalid priority. Choose: %s.'"),
    ],
    "web/modules/custom/crm_teams/crm_teams.info.yml": [
        ("Sales Team A không xem được khách của Team B", "Sales Team A cannot view Team B contacts"),
    ],
    "web/modules/custom/crm_login/crm_login.routing.yml": [
        ('"Đăng nhập"', '"Login"'),
    ],
    "web/modules/custom/crm_import_export/crm_import_export.info.yml": [
        ('"Import và Export dữ liệu CRM từ CSV/Excel"', '"Import and export CRM data from CSV/Excel"'),
    ],
    "web/modules/custom/crm_activity_log/crm_activity_log.routing.yml": [
        ('"Lịch sử tương tác"', '"Activity Log"'),
    ],
    "web/modules/custom/crm_activity_log/crm_activity_log.links.task.yml": [
        ('"Lịch sử tương tác"', '"Activity Log"'),
    ],
    "web/modules/custom/crm_quickadd/crm_quickadd.routing.yml": [
        ('"Thêm khách hàng nhanh"', '"Quick Add Contact"'),
        ('"Thêm cơ hội nhanh"', '"Quick Add Deal"'),
        ('"Thêm tổ chức nhanh"', '"Quick Add Organization"'),
    ],
}

for path, replacements in translations.items():
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    original = content
    for old, new in replacements:
        content = content.replace(old, new)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    changed = "CHANGED" if content != original else "unchanged"
    print(f"[{changed}] {path}")

print("\nAll translations complete.")
