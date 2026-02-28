# Task Attachment – Code Review & Fix Remaining Issues

## Planning
- [x] Read community skills (clean-code, code-review-checklist, production-code-audit, file-uploads, verification-before-completion, architecture)
- [x] Read previous conversation artifacts (walkthrough, implementation plan)
- [x] Read all current source files
- [x] Draft implementation plan
- [ ] User review & approve plan

## Execution
- [ ] Fix: `TaskController.php` – flash messages sai text (actionUpdate + actionUploadAttachment)
- [ ] Fix: `TaskController.php` – commented-out code lines 22, 161-162, 201-204
- [ ] Fix: `TaskAttachmentService.php` – commented-out code lines 45, 50, 62, 111, 162-163
- [ ] Fix: `TaskAttachmentService.php` – `@unlink` error suppression (line 60, 132)
- [ ] Fix: `TaskAttachmentService.php` – `deleteAllByTaskId` delete order (file trước DB → phải DB trước file)
- [ ] Fix: `TaskAttachmentRepository.php` – commented-out code lines 13, 21, 24, 26, 33-34, 36, 38-39, 42
- [ ] Fix: `TaskAttachment.php` – commented-out code line 40
- [ ] Fix: `FileStorageService.php` – commented-out code lines 44-51
- [ ] Fix: `view.php` – AJAX URL hardcoded, missing CSRF token
- [ ] Fix: `TaskAttachmentRepositoryInterface.php` – `deleteAllByTaskId` should be service-only

## Verification
- [ ] Verify all commented-out code removed
- [ ] Verify no `@unlink` error suppression
- [ ] Verify flash messages correct
- [ ] Verify AJAX security (CSRF)
