"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function Home() {
  const router = useRouter();

  useEffect(() => {
    const accepted = localStorage.getItem("acceptedPolicy");

    if (!accepted) {
      router.push("/login");
    }
  }, []);

  return (
    <div>
      หน้า Home
    </div>
  );
}
