"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function PolicyPage() {
  const router = useRouter();
  const [isAccepted, setIsAccepted] = useState(false);

  const handleContinue = () => {
    if (!isAccepted) {
      alert("กรุณายอมรับเงื่อนไขก่อน");
      return;
    }

    localStorage.setItem("policyAccepted", "true");
    router.push("/home");
  };

  const handleDecline = () => {
    localStorage.removeItem("user_id");
    router.push("/login");
  };

  return (
    <div className="app-wrapper">
      <div className="phone-frame">

        <div className="policy-card">

          <h2 className="policy-title">
            📜 นโยบายความเป็นส่วนตัว
          </h2>

          <div className="policy-text">
            <b>ข้อตกลงการใช้งาน Smart Farm</b>
            <br /><br />

            1. แอปพลิเคชันจะเก็บข้อมูล เช่น ชื่อ เบอร์โทร และพิกัด GPS  
            เพื่อใช้วิเคราะห์การทำเกษตร
            <br /><br />

            2. ข้อมูลจะถูกเก็บเป็นความลับ และใช้เพื่อปรับปรุงระบบเท่านั้น
            <br /><br />

            3. แอปอาจขอเข้าถึงตำแหน่งเพื่อคำนวณสภาพอากาศ
            <br /><br />

            หากยอมรับเงื่อนไข กรุณาทำเครื่องหมายด้านล่าง
          </div>

          <div className="policy-check">
            <input
              type="checkbox"
              checked={isAccepted}
              onChange={(e) => setIsAccepted(e.target.checked)}
            />

            <label>
              ฉันยอมรับเงื่อนไขการใช้งานและนโยบายความเป็นส่วนตัว
            </label>
          </div>

          <div className="policy-buttons">

            <button
              onClick={handleContinue}
              className={isAccepted ? "btn-primary" : "btn-disabled"}
            >
              ดำเนินการต่อ
            </button>

          </div>

        </div>

      </div>
    </div>
  );
}