import { test, expect, type Page } from "@playwright/test";
const password = "Password123!";
let createdTicketPath = "";
async function login(page: Page, email: string, path = "/") {
    await page.goto(path);
    await page.getByLabel("Email", { exact: true }).fill(email);
    await page.getByLabel("Mật khẩu", { exact: true }).fill(password);
    await page.getByRole("button", { name: "Vào không gian của tôi" }).click();
    await expect(page.getByRole("button", { name: "Đăng xuất" })).toBeVisible();
}
async function action(page: Page, name: string, fill?: () => Promise<void>) {
    await page.getByRole("button", { name, exact: true }).click();
    const dialog = page.getByRole("dialog");
    await expect(dialog).toBeVisible();
    if (fill) await fill();
    await dialog.getByRole("button", { name: "Xác nhận", exact: true }).click();
    try {
        await expect(dialog).not.toBeVisible();
    } catch {
        throw new Error(
            "Thao tác " + name + " thất bại: " + (await dialog.innerText()),
        );
    }
}
test("Sinh viên → điều phối → cán bộ → bổ sung → xử lý lại → đóng → đánh giá, báo cáo và tệp private", async ({
    browser,
}) => {
    const errors: string[] = [];
    const contexts = await Promise.all(
        [1, 2, 3].map(() => browser.newContext()),
    );
    const [student, coordinator, staff] = await Promise.all(
        contexts.map((c) => c.newPage()),
    );
    for (const p of [student, coordinator, staff])
        p.on("pageerror", (e) => errors.push(e.message));
    await login(student, "student1@demo.local", "/tickets/new");
    const baseline = (await (await student.request.get("/api/reports")).json())
        .data;
    const session = (await contexts[0].cookies()).find(
        (c) => c.name === "student_feedback_e2e_session",
    );
    expect(session?.httpOnly).toBe(true);
    expect(session?.sameSite).toBe("Lax");
    const title = "Kiểm tra E2E " + Date.now();
    await student.getByLabel("Tiêu đề", { exact: true }).fill(title);
    await student
        .getByLabel("Danh mục", { exact: true })
        .selectOption({ label: "Tài khoản sinh viên" });
    await student
        .getByLabel("Nội dung gốc", { exact: true })
        .fill("Không truy cập được cổng học tập lúc 08:30. Đề nghị kiểm tra.");
    await student
        .getByRole("button", { name: "Lưu nháp", exact: true })
        .click();
    await expect(student).toHaveURL(/\/tickets\/\d+$/);
    const path = new URL(student.url()).pathname;
    createdTicketPath = path;
    await expect(
        student.getByRole("heading", { name: "Chỉnh sửa bản nháp" }),
    ).toBeVisible();
    await student
        .locator("input[type=file]")
        .setInputFiles({
            name: "minh-chung.png",
            mimeType: "image/png",
            buffer: Buffer.from(
                "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jWQAAAABJRU5ErkJggg==",
                "base64",
            ),
        });
    await student
        .getByRole("button", { name: "Tải tệp lên", exact: true })
        .click();
    await expect(
        student.getByRole("link", { name: "minh-chung.png" }),
    ).toBeVisible();
    await student
        .getByRole("button", { name: "Gửi hồ sơ", exact: false })
        .click();
    await expect(
        student.getByText("Đã gửi", { exact: true }).first(),
    ).toBeVisible();
    await student.reload();
    await expect(
        student.getByRole("heading", { name: title, exact: true }),
    ).toBeVisible();
    await login(coordinator, "coordinator@demo.local", path);
    await action(coordinator, "Phân công", async () => {
        await coordinator
            .getByLabel("Đơn vị tiếp nhận")
            .selectOption({ label: "Trung tâm Công nghệ thông tin" });
        await coordinator
            .getByLabel("Cán bộ phụ trách", { exact: true })
            .selectOption({ label: "Cán bộ Hoàng Anh" });
    });
    await login(staff, "staff4@demo.local", path);
    await action(staff, "Bắt đầu xử lý");
    await action(staff, "Ghi chú nội bộ", async () => {
        await staff
            .getByLabel("Nội dung chỉ dành cho cán bộ/điều phối")
            .fill("E2E_SECRET_INTERNAL");
    });
    await action(staff, "Yêu cầu bổ sung", async () => {
        await staff
            .getByLabel("Nội dung gửi sinh viên")
            .fill("Vui lòng bổ sung thời điểm phát sinh.");
    });
    await student.reload();
    await expect(
        student.getByText("Chờ bổ sung", { exact: true }).first(),
    ).toBeVisible();
    await expect(student.getByText("E2E_SECRET_INTERNAL")).toHaveCount(0);
    await student
        .getByLabel("Nội dung bổ sung", { exact: true })
        .fill("Em đã kiểm tra lúc 08:30 và vẫn gặp lỗi.");
    const sessionToken = (
        await (await student.request.get("/api/csrf-cookie")).json()
    ).csrf_token;
    await student.request.post("/api/logout", {
        headers: { "X-CSRF-TOKEN": sessionToken },
    });
    await student
        .getByRole("button", { name: "Gửi trao đổi", exact: true })
        .click();
    await expect(
        student.getByRole("heading", { name: "Đăng nhập", exact: true }),
    ).toBeVisible();
    await student
        .getByLabel("Email", { exact: true })
        .fill("student1@demo.local");
    await student.getByLabel("Mật khẩu", { exact: true }).fill(password);
    await student
        .getByRole("button", { name: "Vào không gian của tôi" })
        .click();
    await expect(
        student.getByLabel("Nội dung bổ sung", { exact: true }),
    ).toHaveValue("Em đã kiểm tra lúc 08:30 và vẫn gặp lỗi.");
    await student
        .getByRole("button", { name: "Gửi trao đổi", exact: true })
        .click();
    await expect(
        student.getByText("Đang xử lý", { exact: true }).first(),
    ).toBeVisible();
    await staff.reload();
    await action(staff, "Trả lời chính thức", async () => {
        await staff
            .getByLabel("Nội dung gửi sinh viên")
            .fill("Đã khôi phục quyền đăng nhập.");
    });
    await student.reload();
    await action(student, "Yêu cầu xử lý lại", async () => {
        await student
            .getByLabel("Lý do (bắt buộc)")
            .fill("Vẫn bị lỗi khi xác nhận môn học.");
    });
    await staff.reload();
    await action(staff, "Trả lời chính thức", async () => {
        await staff
            .getByLabel("Nội dung gửi sinh viên")
            .fill("Đã khắc phục bước xác nhận môn học, vui lòng kiểm tra.");
    });
    await student.reload();
    await action(student, "Xác nhận đóng");
    await action(student, "Đánh giá", async () => {
        await student.getByLabel("Mức hài lòng").selectOption("4");
        await student
            .getByLabel("Nhận xét (không bắt buộc)")
            .fill("Đã sử dụng được.");
    });
    await expect(
        student.getByText("Đã sử dụng được.", { exact: true }),
    ).toBeVisible();
    await expect(
        student.getByRole("button", { name: "Đánh giá", exact: true }),
    ).toHaveCount(0);
    await student.goto("/reports");
    await expect(
        student.getByRole("heading", { name: "Báo cáo & thống kê" }),
    ).toBeVisible();
    await expect(
        student.getByText("Điểm hài lòng", { exact: false }),
    ).toBeVisible();
    await expect(
        student.locator(".metric").nth(0).locator("strong"),
    ).toHaveText(String(baseline.total_submitted + 1));
    await expect(
        student.locator(".metric").nth(2).locator("strong"),
    ).toHaveText(String((baseline.by_status.CLOSED || 0) + 1));
    await expect(student.locator(".metric").nth(3).locator("small")).toHaveText(
        String(baseline.rating.total + 1) + " lượt đánh giá",
    );
    await coordinator.goto("/reports");
    await expect(
        coordinator.getByRole("link", { name: "Xuất CSV" }),
    ).toBeVisible();
    await student.goto(path);
    const download = student.waitForEvent("download");
    await student.getByRole("link", { name: "minh-chung.png" }).click();
    expect((await download).suggestedFilename()).toBe("minh-chung.png");
    await student.screenshot({
        path: "storage/app/e2e-student-detail.png",
        fullPage: true,
    });
    expect(errors).toEqual([]);
    await Promise.all(contexts.map((c) => c.close()));
});
test("ADMIN thuần quản trị được, server chặn nội dung; mobile và CSRF hoạt động", async ({
    browser,
}) => {
    const ctx = await browser.newContext({
        viewport: { width: 390, height: 844 },
    });
    const page = await ctx.newPage();
    await login(page, "admin@demo.local");
    await expect(
        page.getByRole("heading", { name: "Quản trị hệ thống", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("link", { name: "Tạo hồ sơ", exact: false }),
    ).toHaveCount(0);
    const studentCtx = await browser.newContext();
    const studentPage = await studentCtx.newPage();
    await login(studentPage, "student1@demo.local", "/tickets");
    await studentPage.locator("tbody a").first().click();
    const forbiddenPath = new URL(studentPage.url()).pathname;
    await studentCtx.close();
    const denied = await page.request.get("/api" + forbiddenPath);
    expect(denied.status()).toBe(403);
    const missingCSRF = await page.request.post("/api/admin/departments", {
        data: { code: "NO_CSRF", name: "Không lưu", is_active: true },
    });
    expect(missingCSRF.status()).toBe(419);
    await page.getByRole("button", { name: "Menu" }).click();
    await page
        .getByRole("link", { name: "Quản trị hệ thống", exact: true })
        .click();
    await expect(
        page.getByRole("heading", { name: "Quản trị hệ thống", exact: true }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Thêm tài khoản" }).click();
    await page.getByLabel("Họ và tên").fill("Tài khoản kiểm thử UI");
    await page
        .getByLabel("Email", { exact: true })
        .fill("e2e-" + Date.now() + "@test.local");
    await page.getByLabel("Mật khẩu (ít nhất 12 ký tự)").fill("Password123!");
    await page
        .getByRole("button", { name: "Lưu thay đổi", exact: true })
        .click();
    await expect(page.getByRole("dialog")).not.toBeVisible();
    await expect(page.getByText("Đã lưu dữ liệu quản trị.")).toBeVisible();
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
        ),
    ).toBe(true);
    await page.screenshot({
        path: "storage/app/e2e-admin-mobile.png",
        fullPage: true,
    });
    await page.getByRole("button", { name: "Đăng xuất" }).click();
    await expect(
        page.getByRole("heading", { name: "Đăng nhập", exact: true }),
    ).toBeVisible();
    await ctx.close();
});
test("Các nhánh rút, từ chối, chuyển đơn vị và mất quyền cán bộ cũ qua UI", async ({
    browser,
}) => {
    const contexts = await Promise.all(
        [1, 2, 3].map(() => browser.newContext()),
    );
    const [s, c, old] = await Promise.all(contexts.map((x) => x.newPage()));
    await login(s, "student1@demo.local", "/tickets/new");
    async function createSent(label: string) {
        await s.goto("/tickets/new");
        await s
            .getByLabel("Tiêu đề", { exact: true })
            .fill(label + " " + Date.now());
        await s
            .getByLabel("Danh mục", { exact: true })
            .selectOption({ label: "Tài khoản sinh viên" });
        await s
            .getByLabel("Nội dung gốc", { exact: true })
            .fill("Đề nghị kiểm tra hồ sơ hỗ trợ trên cổng học tập.");
        await s
            .getByRole("button", { name: "Gửi hồ sơ", exact: false })
            .click();
        await expect(
            s.getByText("Đã gửi", { exact: true }).first(),
        ).toBeVisible();
        return new URL(s.url()).pathname;
    }
    await createSent("E2E rút");
    await action(s, "Rút hồ sơ");
    await expect(s.getByText("Đã rút", { exact: true }).first()).toBeVisible();
    const rejectPath = await createSent("E2E từ chối");
    await login(c, "coordinator@demo.local", rejectPath);
    await action(c, "Từ chối", async () => {
        await c
            .getByLabel("Lý do (bắt buộc)")
            .fill("Ngoài phạm vi hỗ trợ theo minh chứng.");
    });
    await expect(c.getByText("Từ chối", { exact: true }).first()).toBeVisible();
    const transferPath = await createSent("E2E chuyển xử lý");
    await c.goto(transferPath);
    await action(c, "Phân công", async () => {
        await c
            .getByLabel("Đơn vị tiếp nhận")
            .selectOption({ label: "Trung tâm Công nghệ thông tin" });
        await c
            .getByLabel("Cán bộ phụ trách", { exact: true })
            .selectOption({ label: "Cán bộ Hoàng Anh" });
    });
    await login(old, "staff4@demo.local", transferPath);
    await expect(
        old.getByRole("heading", { name: "Thông tin xử lý" }),
    ).toBeVisible();
    await action(c, "Chuyển xử lý", async () => {
        await c
            .getByLabel("Đơn vị tiếp nhận")
            .selectOption({ label: "Phòng Đào tạo" });
        await c
            .getByLabel("Cán bộ phụ trách", { exact: true })
            .selectOption({ label: "Cán bộ Phạm Bình" });
        await c
            .getByLabel("Lý do (bắt buộc)")
            .fill("Chuyển đơn vị phụ trách cổng thông tin.");
    });
    await old.reload();
    await expect(old.getByRole("alert")).toContainText("403");
    await c.goto("/notifications");
    await expect(
        c.getByRole("heading", { name: "Thông báo", exact: true }),
    ).toBeVisible();
    await Promise.all(contexts.map((x) => x.close()));
});

test("Mobile 390px: cả bốn vai trò có trang và menu hoạt động", async ({
    browser,
}) => {
    for (const [role, email, path, title] of [
        ["student", "student1@demo.local", "/tickets", "Yêu cầu của tôi"],
        [
            "coordinator",
            "coordinator@demo.local",
            "/queue",
            "Hàng đợi tiếp nhận",
        ],
        ["staff", "staff1@demo.local", "/assigned", "Công việc được giao"],
        ["admin", "admin@demo.local", "/admin/users", "Quản trị hệ thống"],
    ]) {
        const ctx = await browser.newContext({
            viewport: { width: 390, height: 844 },
        });
        const page = await ctx.newPage();
        await login(page, email, path);
        await expect(
            page.getByRole("heading", { name: title, exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole("status").filter({ hasText: "Đang tải dữ liệu" }),
        ).toHaveCount(0);
        await page.getByRole("button", { name: "Menu" }).click();
        await expect(
            page.getByRole("navigation", { name: "Điều hướng chính" }),
        ).toBeVisible();
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth,
            ),
        ).toBe(true);
        await page.screenshot({
            path: "storage/app/mobile-" + role + ".png",
            fullPage: true,
        });
        await ctx.close();
    }
});
