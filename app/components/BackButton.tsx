"use client";

import styles from "./BackButton.module.css";

type Props = {
  onClick: () => void;
  className?: string;
  variant?: "dark";
};

export default function BackButton({ onClick, className, variant }: Props) {
  return (
    <button
      className={`${styles.btn} ${variant === "dark" ? styles.dark : ""} ${className ?? ""}`}
      onClick={onClick}
      aria-label="ย้อนกลับ"
    >
      ‹
    </button>
  );
}
