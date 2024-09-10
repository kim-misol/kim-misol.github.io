import Image from "next/image";
import Link from "next/link";

export default function ResumePage() {
  return (
    <div className="max-w-4xl mx-auto p-6">
      <h1 className="text-2xl font-bold mb-4">
        이력서
        <Link
          href="https://kim-misol.github.io/uploads/KIM_MISOL_CV.pdf"
          className="ml-2 text-blue-600 hover:text-blue-800"
          target="_blank"
          rel="noopener noreferrer"
        >
        <i className="fa fa-download" aria-hidden="true"></i>
      </Link>
      </h1>

      <div className="text-sm text-gray-700">
        <small>
          싱가포르에서 컴퓨터 공학과 기업 정보 시스템을 전공한 후, 1년 2개월간 싱가포르의 IT 회사{" "}
          <Link href="http://www.sage9.com" className="text-blue-600 hover:font-bold" target="_blank" rel="noopener noreferrer" passHref>
              Sage9 Pte Ltd
          </Link>{" "}
          에서 풀스택 개발자로서 일하며 웹 어플리케이션을 개발하였습니다. <br />
          현재 서울시와 한국벤처협회에서 주최하는 웹/앱 개발자 양성 뉴딜일자리 프로그램에 참여하고 있습니다.
        </small>
      </div>

      <hr className="my-6" />

      <h2 className="text-xl font-bold text-red-600 mb-4">관심사</h2>
      <p className="mb-6">
        <b>Front-End 개발, Back-End 개발, 어플리케이션 개발, 딥러닝</b>
      </p>

      <hr className="my-6" />

      <h2 className="text-xl font-bold text-red-600 mb-4">학력 사항</h2>

      <div className="mb-6">
        <p className="font-bold">Bachelor (Murdoch University of Science)</p>
        <small className="float-right">
          <Link href="https://www.murdoch.edu.au/" className="text-red-600 hover:font-bold" target="_blank" rel="noopener noreferrer" passHref>
              Murdoch, Perth, Australia
          </Link>
        </small>
        <p>
          <small><i>B.S. COMPUTER SCIENCE AND BUSINESS INFORMATION SYSTEM</i></small>
          <small className="float-right">Sep. 2018</small>
        </p>
      </div>

      {/* 추가적인 학력 사항과 경력도 비슷한 방식으로 작성 */}

      <hr className="my-6" />

      <h2 className="text-xl font-bold text-red-600 mb-4">경력</h2>

      <div className="mb-6">
        <p className="font-bold">
          <Link href="http://www.sage9.com" className="text-gray-700 hover:text-blue-600" target="_blank" rel="noopener noreferrer" passHref>
              Sage9 PTE LTD
          </Link>
        </p>
        <small className="text-red-600 float-right">Singapore</small>
        <p>
          <small>사원</small>
          <small className="float-right">Nov. 2018 - Dec. 2019</small>
        </p>
        <ul className="list-disc list-inside ml-4">
          <li>직무: 어플리케이션 개발자</li>
          <li>세부내용: 비지니스 웹사이트 및 E-커머스 웹시스템 개발 (풀스택)</li>
        </ul>
      </div>

      {/* 나머지 경력 및 프로젝트 섹션도 유사한 형식으로 추가 */}

      <hr className="my-6" />

      <h2 className="text-xl font-bold text-red-600 mb-4">특별 활동</h2>

      <div className="mb-6">
        <p className="font-bold">웹/앱 개발자 양성 프로그램</p>
        <small className="text-red-600 float-right">S.Korea</small>
        <p>
          <small>참여자</small>
          <small className="float-right">Apr. 2020 - 현재</small>
        </p>
        <ul className="list-disc list-inside ml-4">
          <li>Android and IOS 모바일 프로그램 개발</li>
          <li>하이브리드 앱 개발</li>
        </ul>
      </div>

      {/* 추가적인 특별 활동도 여기에 포함 */}

      <div className="fixed bottom-5 right-5">
        <Link href="./index_en.html" passHref>
            <Image src="/us.png" alt="English Version" width={40} height={40} />
        </Link>
      </div>
    </div>
  );
}
