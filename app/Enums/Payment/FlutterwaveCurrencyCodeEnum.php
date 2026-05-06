<?php

namespace App\Enums\Payment;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum FlutterwaveCurrencyCodeEnum: string
{
    use InvokableCases, Names, Values;

    case NGN = 'NGN'; // Nigerian Naira
    case GHS = 'GHS'; // Ghanaian Cedi
    case USD = 'USD'; // United States Dollar
    case GBP = 'GBP'; // British Pound Sterling
    case EUR = 'EUR'; // Euro
    case KES = 'KES'; // Kenyan Shilling
    case ZAR = 'ZAR'; // South African Rand
    case XAF = 'XAF'; // Central African CFA Franc
    case XOF = 'XOF'; // West African CFA Franc
    case UGX = 'UGX'; // Ugandan Shilling
    case RWF = 'RWF'; // Rwandan Franc
    case TZS = 'TZS'; // Tanzanian Shilling
    case ZMW = 'ZMW'; // Zambian Kwacha
    case XCD = 'XCD'; // East Caribbean Dollar
    case CAD = 'CAD'; // Canadian Dollar
    case GMD = 'GMD'; // Gambian Dalasi
    case SLL = 'SLL'; // Sierra Leonean Leone
    case MGA = 'MGA'; // Malagasy Ariary
    case BWP = 'BWP'; // Botswana Pula
    case EGP = 'EGP'; // Egyptian Pound
    case MAD = 'MAD'; // Moroccan Dirham
    case GNF = 'GNF'; // Guinean Franc
    case CDF = 'CDF'; // Congolese Franc
    case LRD = 'LRD'; // Liberian Dollar
    case MWK = 'MWK'; // Malawian Kwacha
    case SZL = 'SZL'; // Swazi Lilangeni
    case SCR = 'SCR'; // Seychellois Rupee
    case MRU = 'MRU'; // Mauritanian Ouguiya
    case BIF = 'BIF'; // Burundian Franc
    case KMF = 'KMF'; // Comorian Franc
    case STN = 'STN'; // São Tomé and Príncipe Dobra
    case LSL = 'LSL'; // Lesotho Loti
    case NAD = 'NAD'; // Namibian Dollar
}
