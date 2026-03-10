# QUICK TEST CHECKLIST - Views Table UI

**Ngày test**: [Ngày hôm nay]  
**Tester**: [Tên của bạn]  
**Status**: ⬜ Chưa test | 🟡 Đang test | ✅ Passed | ❌ Failed

---

## 🖥️ DESKTOP TEST (Desktop / Laptop)

### URL: http://open-crm.ddev.site/crm/all-contacts

**Kiểm tra**:

- [ ] **Header hiển thị rõ ràng**
  - [ ] Nền tối (gradient)
  - [ ] Chữ trắng
  - [ ] Border xanh ở dưới header
  - [ ] Font đậm, UPPERCASE
- [ ] **Header sticky khi cuộn**
  - [ ] Scroll down
  - [ ] Header vẫn ở top
  - [ ] Không bị cuộn lên
- [ ] **Tiêu đề cột đầy đủ**
  - [ ] NAME ✔
  - [ ] OWNER ✔
  - [ ] ORGANIZATION ✔
  - [ ] PHONE ✔
  - [ ] EMAIL ✔
  - [ ] SOURCE ✔
  - [ ] CUSTOMER TYPE ✔
  - [ ] ACTIONS ✔
- [ ] **Dữ liệu hàng hiển thị**
  - [ ] Dữ liệu được render đúng
  - [ ] Không bị lẫn lộn cột
  - [ ] Format đúng (email, phone, etc.)
- [ ] **Hover effects**
  - [ ] Hover hàng → background đổi màu
  - [ ] Có shadow xuất hiện
  - [ ] Color thay đổi từ hơi sang đậm
- [ ] **Links hoạt động**
  - [ ] Name (title) clickable
  - [ ] Owner clickable
  - [ ] Organization clickable
  - [ ] Email clickable
  - [ ] Hover: underline xuất hiện

- [ ] **Buttons hoạt động**
  - [ ] Edit button (xanh) visible
  - [ ] Delete button (đỏ) visible
  - [ ] Click lại responsive

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 📱 MOBILE TEST (Tablet / Phone)

### URL: http://open-crm.ddev.site/crm/all-contacts

**Setup**:

- Mở DevTools (F12)
- Ctrl+Shift+M (Device Toolbar)
- Chọn: iPhone 12 Pro, iPad, hoặc Samsung Galaxy

**Kiểm tra**:

- [ ] **Header ẩn đi**
  - [ ] Không thấy table header
  - [ ] Header bị `display: none`
- [ ] **Card layout xuất hiện**
  - [ ] Mỗi contact = 1 card
  - [ ] Card có border + shadow
  - [ ] Border radius 8px
- [ ] **Tên cột hiển thị trong card**
  - [ ] "NAME: John Smith"
  - [ ] "OWNER: Sales Rep"
  - [ ] "ORGANIZATION: Acme Corp"
  - [ ] Mỗi field có label
- [ ] **Spacing tốt**
  - [ ] Không quá chặt
  - [ ] Dễ đọc
  - [ ] Padding 12px 16px
- [ ] **Cuộn ngang hoạt động**
  - [ ] Cuộn smooth
  - [ ] Không có jank
  - [ ] Scroll shadow (nếu có)
- [ ] **Buttons visible**
  - [ ] Edit button visible
  - [ ] Delete button visible
  - [ ] Dễ click (not too small)
- [ ] **Links clickable**
  - [ ] Dễ tap trên mobile
  - [ ] Hit area ≥ 44px
  - [ ] Visual feedback rõ

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 🎨 STYLING TEST

### Desktop

- [ ] **Color Contrast**
  - [ ] Header: Trắng on Tối OK
  - [ ] Body: Tối on Trắng OK
  - [ ] Links: Xanh OK

- [ ] **Font**
  - [ ] Header: Bold, 13px, UPPERCASE
  - [ ] Body: Regular, 14px
  - [ ] Links: 500 weight

- [ ] **Spacing**
  - [ ] Header padding: 16px 20px
  - [ ] Body padding: 16px 20px
  - [ ] Row margin: 0
  - [ ] Cell gap: OK

- [ ] **Hover**
  - [ ] Row hover: background change
  - [ ] Link hover: color + underline
  - [ ] Button hover: color + shadow
  - [ ] Transition smooth (0.2s)

**Result**: ⬜ | 🟡 | ✅ | ❌

### Mobile

- [ ] **Card Style**
  - [ ] Border: 1px solid #e2e8f0
  - [ ] Border-radius: 8px
  - [ ] Shadow: 0 1px 3px
  - [ ] Margin: 12px 0

- [ ] **Mobile Font**
  - [ ] Header label: 11px
  - [ ] Body text: 13px
  - [ ] Readable
  - [ ] Label bold

- [ ] **Mobile Spacing**
  - [ ] Padding inside card: 12px 16px
  - [ ] Gap between fields: 12px
  - [ ] Not cramped

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## ♿ ACCESSIBILITY TEST

- [ ] **Keyboard Navigation**
  - [ ] Tab through links OK
  - [ ] Shift+Tab backward OK
  - [ ] Focus visible (outline)
  - [ ] No keyboard traps

- [ ] **Focus Visible**
  - [ ] Tab to link
  - [ ] See outline
  - [ ] Outline color: #3b82f6
  - [ ] Outline width: 2px

- [ ] **Color Vision**
  - [ ] Not rely on color alone
  - [ ] Icons + labels
  - [ ] Text contrast OK
  - [ ] 4.5:1 ratio minimum

- [ ] **Screen Reader** (if available)
  - [ ] Table structure recognized
  - [ ] Headers announced
  - [ ] Row/cell announced
  - [ ] Links identified

- [ ] **High Contrast Mode** (Windows)
  - [ ] Activate High Contrast
  - [ ] Colors adjust
  - [ ] Still readable
  - [ ] 7:1 contrast OK

- [ ] **Dark Mode** (macOS/Windows)
  - [ ] Activate Dark Mode
  - [ ] Colors adaptive
  - [ ] Still readable
  - [ ] No pure black

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 📊 DATA TEST

**Sample Data**: Verify at least 3 contacts

- [ ] **Row 1 Data Correct**
  - [ ] Name: [_________]
  - [ ] Owner: [_________]
  - [ ] Org: [_________]
  - [ ] Phone: [_________]
  - [ ] Email: [_________]

- [ ] **Row 2 Data Correct**
  - [ ] Name: [_________]
  - [ ] Owner: [_________]
  - [ ] Org: [_________]
  - [ ] Phone: [_________]
  - [ ] Email: [_________]

- [ ] **Row 3 Data Correct**
  - [ ] Name: [_________]
  - [ ] Owner: [_________]
  - [ ] Org: [_________]
  - [ ] Phone: [_________]
  - [ ] Email: [_________]

- [ ] **No Data Errors**
  - [ ] No missing fields
  - [ ] No duplicates
  - [ ] Email format OK
  - [ ] Phone format OK

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 🔗 INTERACTION TEST

- [ ] **Click Name**
  - [ ] Goes to contact detail page
  - [ ] URL changes to `/node/[ID]`
  - [ ] Content correct

- [ ] **Click Owner**
  - [ ] Goes to user profile
  - [ ] URL changes
  - [ ] User info correct

- [ ] **Click Organization**
  - [ ] Goes to org detail
  - [ ] Correct organization page
  - [ ] Data matches

- [ ] **Click Email**
  - [ ] Opens email client
  - [ ] Correct recipient
  - [ ] Mailto link works

- [ ] **Click Edit Button**
  - [ ] Opens edit modal/page
  - [ ] Can edit fields
  - [ ] Can save changes

- [ ] **Click Delete Button**
  - [ ] Confirmation dialog
  - [ ] Can delete
  - [ ] Removed from list

- [ ] **Paging**
  - [ ] Page numbers visible
  - [ ] Click to other page
  - [ ] Data changes
  - [ ] Back works

- [ ] **Sorting**
  - [ ] Click header
  - [ ] Rows reorder
  - [ ] Column indicator visible
  - [ ] Asc/Desc works

- [ ] **Filter**
  - [ ] Search by name works
  - [ ] Search by email works
  - [ ] Reset button works
  - [ ] Results update

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 🖨️ PRINT TEST

- [ ] **Print Preview** (Ctrl+P)
  - [ ] Layout looks OK
  - [ ] Header visible
  - [ ] Data readable
  - [ ] Action buttons hidden

- [ ] **Save as PDF**
  - [ ] Opens without errors
  - [ ] Formatting correct
  - [ ] All columns visible
  - [ ] No overflow

- [ ] **Physical Print**
  - [ ] Paper size OK
  - [ ] Margins OK
  - [ ] Page breaks OK
  - [ ] Quality good

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 🌐 BROWSER TEST

Test trên ít nhất 2 browsers:

### Chrome

- [ ] Desktop ✅ / ❌
- [ ] Mobile ✅ / ❌
- [ ] Mobile (iOS) ✅ / ❌

### Firefox

- [ ] Desktop ✅ / ❌
- [ ] Mobile ✅ / ❌

### Safari

- [ ] Desktop ✅ / ❌
- [ ] Mobile (iOS) ✅ / ❌

### Edge

- [ ] Desktop ✅ / ❌
- [ ] Mobile ✅ / ❌

**Result**: ✅ All pass | 🟡 Some issues | ❌ Major issues

---

## 📈 PERFORMANCE TEST

- [ ] **Load Time**
  - [ ] Page loads < 3s
  - [ ] Table renders < 1s
  - [ ] CSS loads < 100ms

- [ ] **No Jank**
  - [ ] Scroll smooth
  - [ ] Hover smooth
  - [ ] No 60fps drops
  - [ ] DevTools: Performance OK

- [ ] **No Memory Leaks**
  - [ ] DevTools: Memory stable
  - [ ] No infinite loops
  - [ ] No console errors

**Result**: ⬜ | 🟡 | ✅ | ❌

---

## 🐛 BUGS FOUND

| #   | Bug     | Severity                  | Status             | Notes  |
| --- | ------- | ------------------------- | ------------------ | ------ |
| 1   | [_____] | 🔴 High / 🟡 Med / 🟢 Low | ⬜ Open / ✅ Fixed | [____] |
| 2   | [_____] | 🔴 High / 🟡 Med / 🟢 Low | ⬜ Open / ✅ Fixed | [____] |
| 3   | [_____] | 🔴 High / 🟡 Med / 🟢 Low | ⬜ Open / ✅ Fixed | [____] |

---

## 📝 NOTES & COMMENTS

```
[Ghi chú của bạn ở đây]


```

---

## ✅ FINAL VERDICT

### Overall Status

- **Desktop**: ✅ Passed / 🟡 Mostly OK / ❌ Failed
- **Mobile**: ✅ Passed / 🟡 Mostly OK / ❌ Failed
- **Accessibility**: ✅ Passed / 🟡 Mostly OK / ❌ Failed
- **Browser Compat**: ✅ All OK / 🟡 Most OK / ❌ Issues
- **Performance**: ✅ Excellent / 🟡 OK / ❌ Slow

### Recommendation

- ✅ **READY FOR PRODUCTION** - All tests pass
- 🟡 **READY WITH FIXES** - Minor issues fixed
- ❌ **NEEDS MORE WORK** - Major issues found

---

## 📋 SIGN OFF

**Tested by**: **********\_\_\_\_**********  
**Date**: **********\_\_\_\_**********  
**Time spent**: **********\_\_\_\_**********  
**Approved by**: **********\_\_\_\_**********

---

**Note**: Lưu file này sau khi hoàn thành test để track tiến độ
