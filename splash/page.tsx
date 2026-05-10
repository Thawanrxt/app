"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function SplashPage() {
  const router = useRouter();

  useEffect(() => {
  const timer = setTimeout(() => {
      router.push("/login"); // ไปหน้า login
    }, 2500); 

    return () => clearTimeout(timer);
  }, [router]);

  return (
    <div className="app-wrapper">
      <div className="phone-frame">
        <div className="splash-container">
          <div className="logo-box">logo</div>
        </div>
      </div>
    </div>
  );
}
