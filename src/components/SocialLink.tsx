import React from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

interface SocialLinkProps {
  href: string;
  icon: any; // Icon type imported from FontAwesome
}

export default function SocialLink({ href, icon }: SocialLinkProps) {
  return (
    <a
      href={href}
      className="mb-2 text-white hover:text-blue-500"
      target="_blank"
      rel="noopener noreferrer"
    >
      <FontAwesomeIcon 
        icon={icon} 
        className="flex-shrink-0 text-sm sm:text-lg md:text-xl lg:text-2xl min-w-[24px]" 
        />
    </a>
  );
}
