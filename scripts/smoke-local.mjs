import fs from "node:fs";
import { chromium } from "@playwright/test";
const browser = await chromium.launch({ channel: "chrome" });
const result = { url: "http://127.0.0.1:8000", verified_at_utc: new Date().toISOString(), roles: [] };
try {
    for (const [role,email,total] of [
        ["STUDENT","student1@demo.local",4],["COORDINATOR","coordinator@demo.local",92],
        ["STAFF","staff1@demo.local",33],["ADMIN","admin@demo.local",0]
    ]) {
        const ctx = await browser.newContext();const page = await ctx.newPage();const errors=[];
        page.on("pageerror",e=>errors.push(e.message));
        await page.goto(result.url);
        await page.getByLabel("Email",{exact:true}).fill(email);await page.getByLabel("Mật khẩu",{exact:true}).fill("Password123!");
        await page.getByRole("button",{name:"Vào không gian của tôi"}).click();
        await page.getByRole("button",{name:"Đăng xuất"}).waitFor({timeout:30000});
        const response=await page.request.get(result.url+"/api/dashboard");const report=(await response.json()).data;
        if(response.status()!==200||report.total_submitted!==total||errors.length)throw new Error(JSON.stringify({role,status:response.status(),report,errors}));
        await page.locator(".welcome").waitFor();await page.screenshot({path:"storage/app/dashboard-"+role.toLowerCase()+".png",fullPage:true});
        result.roles.push({role,email,total_submitted:report.total_submitted,overdue_open:report.overdue_open,administration:report.administration});
        await ctx.close();
    }
    fs.writeFileSync("docs/local-smoke.json",JSON.stringify(result,null,2)+"\n");
    console.log(JSON.stringify(result,null,2));
} finally {await browser.close();}
