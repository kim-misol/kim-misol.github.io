import Link from "next/link";
import { icons } from "../utils/icons";
import SocialLink from "./SocialLink";

export default function Sidebar() {
  return (
    <div className="w-64 bg-gray-800 text-white h-screen flex flex-col justify-end p-4">
      {/* 하단 콘텐츠 */}
      <div>
        <h1 className="text-5xl font-bold">
          <Link href="/">김미솔</Link>
        </h1>
        <p className="mt-2 text-gray-400">Software Developer</p>

        <nav className="flex items-center space-x-4 my-4" >
          <SocialLink href="mailto:misolkim94@gmail.com" icon={icons.envelope} />
          <SocialLink href="https://github.com/kim-misol/" icon={icons.github} />
          <SocialLink href="https://www.linkedin.com/in/misolkim/" icon={icons.linkedin} />
        </nav>

        <span className="block text-gray-400">Currently v2.0.0</span>
        <p className="text-gray-500 mt-4">&copy; 2024. All rights reserved.</p>
      </div>
    </div>
  );
}
